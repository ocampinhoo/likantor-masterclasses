# Likantor Masterclasses

Plataforma web PHP/MySQL para vender y administrar Masterclasses en línea.

## Requisitos

- PHP 8.1+
- MySQL 5.7+ / MariaDB 10.3+
- Apache con mod_rewrite
- Composer

## Instalación local

```bash
composer install
cp .env.example .env
# Editar .env con credenciales de BD
mysql -u root -p < database/schema.sql
```

Configurar el document root apuntando a `/public`.

### Credenciales admin (después de importar schema.sql)

- Email: `admin@likantor.com`
- Contraseña temporal: `password` — **cambiar inmediatamente en producción**

## Hostinger

1. Subir archivos (excepto `.env`)
2. Document root → `public/`
3. Crear base de datos e importar `database/schema.sql`
4. Copiar `.env.example` a `.env` y completar variables
5. Permisos de escritura en `storage/logs` y `storage/cache`

## Sistema de leads (temario / información)

- Formularios públicos en la landing y páginas de Masterclass (`name`, `email`, checkbox de privacidad).
- Flujo: validación frontend (JS) → validación backend + CSRF → honeypot anti-bot → rate limiting
  (tabla `rate_limits`, 5 intentos / 10 min por IP) → verificación de formato/dominio de email →
  guardado deduplicado en `leads` (upsert por email) + histórico en `lead_interactions` → envío
  del temario vía SendGrid (`email_queue` → `email_templates` → `email_logs`) → redirección a
  `/gracias-temario`.
- El template de email "Temario / información de la Masterclass" vive en la tabla
  `email_templates` (slug `syllabus_request`). Su columna `body_html` puede editarse en cualquier
  momento para sustituir el contenido por el PDF/temario definitivo, o bien asignarle un
  `sendgrid_template_id` para usar un Dynamic Template de SendGrid en su lugar.
- UTMs (`utm_source`, `utm_medium`, `utm_campaign`, `utm_content`, `utm_term`) y `campaign` se
  capturan automáticamente desde la URL de llegada (por ejemplo, enlaces de Meta Ads) y se
  guardan junto con el lead.
- La API key de SendGrid (`SENDGRID_API_KEY` en `.env`) solo se usa desde PHP
  (`App\Services\SendGridService`); nunca se expone al navegador.
- Cron recomendado (cada 5 minutos) para reintentar envíos fallidos:

```bash
php /ruta/al/proyecto/cron/process_email_queue.php
```

- Si la base de datos ya existía antes de este cambio, ejecutar la migración:

```bash
mysql -u usuario -p likantor_masterclasses < database/migrations/002_lead_tracking_and_rate_limits.sql
```

## Registro, login y recuperación de contraseña

- **Registro** (`/registro`): nombre, email, contraseña (mínimo 10 caracteres, `password_hash()`),
  confirmación y checkbox obligatorio de Aviso de Privacidad/Términos. La respuesta es **siempre
  genérica** ("revisa tu correo"), exista o no ya una cuenta con ese email, para evitar
  enumeración de usuarios. Tras registrarse se envía un correo de verificación con un token de un
  solo uso (hash SHA-256 en `email_verification_tokens`, expira en 24h).
- **Verificación de email** (`/verificar-email/{token}` y reenvío en `/verificar-email/reenviar`):
  al verificar, se inicia sesión automáticamente. El área privada (`/mi-cuenta`) muestra un aviso
  con botón de reenvío si el correo sigue sin verificarse (no bloquea el acceso, solo informa).
- **Login** (`/login`): rate limiting persistido en MySQL (tabla `rate_limits`, 5 intentos / 15 min
  por email+IP), mensajes de error genéricos ("Credenciales incorrectas") y comparación de
  contraseña en tiempo constante incluso cuando el email no existe (evita enumeración por timing).
  `session_regenerate_id()` tras cada login, cookies `HttpOnly` + `SameSite=Lax` + `Secure` en
  HTTPS.
- **Recordarme**: checkbox opcional en el login. Usa el patrón selector/validador (tabla
  `remember_tokens`): la cookie solo contiene un selector público y un validador aleatorio de alta
  entropía; en BD únicamente se guarda el hash del validador. El token se **rota** en cada uso
  automático y, si se detecta un validador inválido para un selector existente (indicio de robo de
  cookie), se revocan todos los tokens del usuario.
- **Recuperar contraseña** (`/recuperar-contrasena` → `/restablecer-contrasena/{token}`): igual que
  el registro, la respuesta es siempre genérica y nunca confirma si el email existe. Tokens de un
  solo uso con expiración de 60 minutos (`password_reset_tokens`, hash SHA-256). Al restablecer la
  contraseña se invalidan todos los tokens de reset pendientes y todas las sesiones "recordarme"
  del usuario, y se marca el email como verificado (poseer el enlace ya demuestra su titularidad).
- Todos los formularios de auth incluyen token CSRF (`csrf_field()` / `CsrfMiddleware`).
- Templates de email `email_verification` y `password_reset` en `email_templates` (editable sin
  tocar código). Si la base de datos ya existía, ejecutar:

```bash
mysql -u usuario -p likantor_masterclasses < database/migrations/003_auth_tokens_and_email_templates.sql
```

## Sistema de pagos (Stripe + Mercado Pago)

### Flujo

```
Usuario autenticado → /checkout/{slug} → elige proveedor
   → se crea un intento de pago (payments, status=pending, SIN inscripción todavía)
   → redirección al checkout hospedado del proveedor (Stripe Checkout / MP Checkout Pro)
   → el usuario paga
   → el proveedor llama a /webhooks/{stripe|mercadopago}
   → se valida la firma del webhook (Stripe-Signature / x-signature)
   → se registra el evento en payment_events (idempotencia)
   → se actualiza payments.status
   → SOLO si status = approved: se crea/confirma la inscripción (enrollments),
     se activa el acceso y SendGrid envía "Pago confirmado"
```

**Regla fundamental:** un pago nunca se considera confirmado por llegar a `/pago/exito`; esas páginas
(`/pago/exito`, `/pago/pendiente`, `/pago/error`) solo muestran el estado más reciente guardado en
BD y siempre aclaran que la confirmación depende del webhook. La inscripción (`enrollments`) se
crea **exclusivamente** cuando `PaymentService::applyStatusUpdate()` recibe un estado `approved`
proveniente de un webhook válido; nunca con `pending`, `failed`, `cancelled` ni por la redirección
de retorno del navegador.

### Base de datos

- `payments`: `uuid`, `user_id`, `masterclass_id`, `enrollment_id` (NULL hasta que se aprueba),
  `provider` (`stripe`/`mercadopago`), `provider_payment_id`, `provider_preference_id`, `amount`,
  `currency`, `status` (`pending`, `approved`, `failed`, `cancelled`, `refunded`, `chargeback`,
  `unknown`), `idempotency_key` (único), `metadata` (JSON — incluye `commercial_price` /
  `commercial_currency` para trazabilidad).
- `payment_events`: una fila por evento único de webhook (`provider` + `payload_hash` es único);
  reintentos/reenvíos del proveedor con el mismo evento nunca se reprocesan.
- `enrollments.status`: `paid` (acceso activo), `refunded`, `access_revoked` (tras contracargo),
  etc. Solo existe una fila por `(user_id, masterclass_id)`.

Si la base de datos ya existía, ejecutar:

```bash
mysql -u usuario -p likantor_masterclasses < database/migrations/004_payments_gateways.sql
```

### Moneda: precio comercial vs. moneda del checkout vs. monto recibido

El precio comercial de la Masterclass (`masterclasses.price` / `masterclasses.currency`, 65 USD) es
el valor de referencia. `payments.amount` / `payments.currency` reflejan lo que **realmente** se
solicitó/recibió en el checkout de cada proveedor:

- **Stripe**: procesa directamente en la moneda comercial (USD), sin conversión.
- **Mercado Pago**: si tu cuenta no puede liquidar en USD, define explícitamente en `.env` la
  moneda y el monto reales del checkout (`MERCADOPAGO_CHECKOUT_CURRENCY`,
  `MERCADOPAGO_CHECKOUT_AMOUNT`). El sistema **nunca** calcula una conversión automática: si no
  configuras estas variables, se intenta usar la misma moneda/monto comercial. El precio comercial
  original siempre queda guardado en `payments.metadata.commercial_price/commercial_currency` para
  auditoría, sin importar en qué moneda se haya cobrado.

### Webhooks

- `POST /webhooks/stripe` y `POST /webhooks/mercadopago` son públicos (sin login, sin CSRF) pero
  validan la autenticidad de cada petición:
  - **Stripe**: se verifica manualmente el header `Stripe-Signature` (esquema
    `t=timestamp,v1=firma`, HMAC-SHA256 con `STRIPE_WEBHOOK_SECRET`, con tolerancia anti-replay).
  - **Mercado Pago**: se verifica el header `x-signature` (`ts=...,v1=...`, manifest
    `id:...;request-id:...;ts:...;` firmado con `MERCADOPAGO_WEBHOOK_SECRET`) y, además, el detalle
    del pago **siempre** se reconsulta a la API oficial de Mercado Pago — nunca se confía en el
    cuerpo del webhook por sí solo.
- Cada evento recibido se registra en `payment_events` (incluye el payload crudo) antes de
  procesarse, y el procesamiento es idempotente a dos niveles: (1) el mismo evento nunca se procesa
  dos veces, y (2) un pago que ya está `approved` no vuelve a crear inscripción, acceso ni correo
  aunque llegue un segundo evento de aprobación.
- No se procesan tarjetas ni se almacenan datos sensibles (PAN, CVV): todo el cobro ocurre en la
  página hospedada de Stripe/Mercado Pago.

### Probar webhooks en desarrollo sin credenciales reales

1. En tu `.env` local, deja `STRIPE_SECRET_KEY` y `MERCADOPAGO_ACCESS_TOKEN` **vacíos** (no
   necesitas cuentas reales), pero define **sí** un valor para `STRIPE_WEBHOOK_SECRET` y
   `MERCADOPAGO_WEBHOOK_SECRET` (puede ser cualquier cadena inventada, p. ej. `dev_local_secret`).
2. Con `APP_ENV=local`, al intentar pagar desde `/checkout/{slug}` el sistema detecta que no hay
   credenciales reales y te redirige automáticamente a un simulador local
   (`/dev/pagos/{uuid}`) en vez de llamar a Stripe/Mercado Pago.
3. Ahí eliges el resultado a simular (aprobado, pendiente, rechazado, cancelado, reembolsado). El
   simulador construye un evento con la forma exacta que enviaría cada proveedor, lo firma con el
   secreto de tu `.env` y lo envía (HTTP, en loopback) al mismo endpoint `/webhooks/{provider}` que
   usarías en producción — es decir, ejercita el código real de validación de firma, idempotencia y
   activación de acceso, no un atajo separado.
4. Esta ruta (`App\Controllers\Dev\PaymentSimulatorController`) responde 404 automáticamente si
   `APP_ENV` no es `local`, por lo que no representa un riesgo en producción aunque quedara
   desplegada por error.
5. Alternativa por línea de comandos (útil para pruebas automatizadas, sin navegador):

```bash
php cron/simulate_webhook.php --payment=<uuid-del-pago> --outcome=approved
```

   Acepta `--outcome=approved|failed|cancelled|refunded|pending`. Usa la misma clase
   (`App\Services\PaymentWebhookSimulator`) que el simulador web, así que el resultado es
   idéntico; también falla explícitamente si `APP_ENV` no es `local`.

### Admin

`/admin/pagos` muestra: total de ventas aprobadas, conteo por estado (aprobados, pendientes,
fallidos/cancelados, reembolsos/contracargos), ingresos **agrupados por moneda** (nunca se suman
monedas distintas entre sí) y la tabla de pagos recientes con folio, usuario, proveedor, monto y
estado. `/admin/registros` muestra las inscripciones confirmadas con su pago asociado.

## Estructura

Ver documentación de arquitectura en el repositorio.
