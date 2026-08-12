-- Likantor Masterclasses — Schema completo
-- MySQL/MariaDB · InnoDB · utf8mb4

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS likantor_masterclasses
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE likantor_masterclasses;

-- ---------------------------------------------------------------------------
-- users
-- ---------------------------------------------------------------------------
CREATE TABLE users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    pronouns VARCHAR(50) NULL,
    professional_title VARCHAR(150) NULL,
    age TINYINT UNSIGNED NULL,
    role ENUM('user', 'admin', 'super_admin') NOT NULL DEFAULT 'user',
    privacy_accepted_at DATETIME NOT NULL,
    email_verified_at DATETIME NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_users_uuid (uuid),
    UNIQUE KEY uk_users_email (email),
    KEY idx_users_role (role),
    KEY idx_users_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- masterclasses
-- ---------------------------------------------------------------------------
CREATE TABLE masterclasses (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug VARCHAR(120) NOT NULL,
    name VARCHAR(255) NOT NULL,
    subtitle VARCHAR(255) NULL,
    description TEXT NULL,
    instructor_name VARCHAR(150) NOT NULL,
    instructor_bio TEXT NULL,
    instructor_image VARCHAR(255) NULL,
    hero_image VARCHAR(255) NULL,
    syllabus JSON NULL,
    event_starts_at DATETIME NOT NULL COMMENT 'Almacenado en UTC',
    timezone VARCHAR(64) NOT NULL DEFAULT 'America/Mexico_City',
    duration_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 180,
    price DECIMAL(10, 2) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'USD',
    modality VARCHAR(50) NOT NULL DEFAULT 'online_zoom',
    zoom_meeting_url TEXT NULL,
    zoom_meeting_id VARCHAR(50) NULL,
    zoom_passcode VARCHAR(255) NULL,
    recording_url TEXT NULL,
    registration_closes_at DATETIME NULL,
    published_at DATETIME NULL,
    status ENUM('draft', 'published', 'registration_closed', 'live', 'completed', 'archived') NOT NULL DEFAULT 'draft',
    max_capacity SMALLINT UNSIGNED NULL,
    meta_title VARCHAR(255) NULL,
    meta_description TEXT NULL,
    disclaimer_text TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_masterclasses_slug (slug),
    KEY idx_masterclasses_status (status),
    KEY idx_masterclasses_published_at (published_at),
    KEY idx_masterclasses_event_starts_at (event_starts_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- enrollments
-- ---------------------------------------------------------------------------
CREATE TABLE enrollments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    masterclass_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending', 'awaiting_payment', 'paid', 'cancelled', 'refunded', 'access_revoked') NOT NULL DEFAULT 'pending',
    payment_id BIGINT UNSIGNED NULL,
    access_granted_at DATETIME NULL,
    zoom_revealed_at DATETIME NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_enrollments_user_masterclass (user_id, masterclass_id),
    KEY idx_enrollments_status (status),
    KEY idx_enrollments_masterclass_id (masterclass_id),
    CONSTRAINT fk_enrollments_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_enrollments_masterclass FOREIGN KEY (masterclass_id) REFERENCES masterclasses (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- payments
-- ---------------------------------------------------------------------------
-- NOTA: enrollment_id es NULL mientras el pago está pending/failed/cancelled/unknown.
-- Por diseño, la inscripción (enrollments) solo se crea/confirma cuando el webhook
-- del proveedor reporta el pago como 'approved' (ver App\Services\PaymentService).
CREATE TABLE payments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    masterclass_id BIGINT UNSIGNED NOT NULL,
    enrollment_id BIGINT UNSIGNED NULL,
    provider ENUM('mercadopago', 'stripe') NOT NULL,
    provider_payment_id VARCHAR(255) NULL,
    provider_preference_id VARCHAR(255) NULL,
    amount DECIMAL(10, 2) NOT NULL COMMENT 'Monto solicitado/recibido en el checkout de este proveedor (ver metadata.commercial_price para el precio comercial)',
    currency CHAR(3) NOT NULL,
    status ENUM('pending', 'approved', 'failed', 'cancelled', 'refunded', 'chargeback', 'unknown') NOT NULL DEFAULT 'pending',
    failure_reason VARCHAR(255) NULL,
    webhook_received_at DATETIME NULL,
    idempotency_key VARCHAR(255) NOT NULL,
    metadata JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_payments_uuid (uuid),
    UNIQUE KEY uk_payments_idempotency (idempotency_key),
    KEY idx_payments_provider_payment (provider, provider_payment_id),
    KEY idx_payments_status (status),
    KEY idx_payments_user_id (user_id),
    KEY idx_payments_masterclass_id (masterclass_id),
    KEY idx_payments_enrollment_id (enrollment_id),
    CONSTRAINT fk_payments_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_payments_masterclass FOREIGN KEY (masterclass_id) REFERENCES masterclasses (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_payments_enrollment FOREIGN KEY (enrollment_id) REFERENCES enrollments (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE enrollments
    ADD CONSTRAINT fk_enrollments_payment FOREIGN KEY (payment_id) REFERENCES payments (id) ON DELETE SET NULL ON UPDATE CASCADE;

-- ---------------------------------------------------------------------------
-- payment_events — bitácora de idempotencia de webhooks (una fila por evento
-- único recibido; provider_event_id/payload_hash garantizan que reintentos o
-- reenvíos del proveedor nunca se procesen dos veces)
-- ---------------------------------------------------------------------------
CREATE TABLE payment_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    payment_id BIGINT UNSIGNED NULL,
    provider VARCHAR(50) NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    provider_event_id VARCHAR(255) NULL,
    payload_hash CHAR(64) NOT NULL,
    payload MEDIUMTEXT NULL COMMENT 'Cuerpo crudo del evento, para auditoría/reproceso manual',
    processed TINYINT(1) NOT NULL DEFAULT 0,
    error_message TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_payment_events_idempotency (provider, payload_hash),
    KEY idx_payment_events_payment_id (payment_id),
    KEY idx_payment_events_provider_event_id (provider, provider_event_id),
    CONSTRAINT fk_payment_events_payment FOREIGN KEY (payment_id) REFERENCES payments (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- leads (registro deduplicado por email — "última interacción")
-- ---------------------------------------------------------------------------
CREATE TABLE leads (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    masterclass_id BIGINT UNSIGNED NULL,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(255) NOT NULL,
    source VARCHAR(50) NOT NULL DEFAULT 'syllabus_form',
    campaign VARCHAR(100) NULL,
    utm_source VARCHAR(100) NULL,
    utm_medium VARCHAR(100) NULL,
    utm_campaign VARCHAR(150) NULL,
    utm_content VARCHAR(150) NULL,
    utm_term VARCHAR(150) NULL,
    ip_address VARBINARY(16) NULL,
    user_agent VARCHAR(255) NULL,
    privacy_accepted_at DATETIME NOT NULL,
    email_sent_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_leads_email (email),
    KEY idx_leads_masterclass_id (masterclass_id),
    KEY idx_leads_created_at (created_at),
    KEY idx_leads_utm_campaign (utm_campaign),
    CONSTRAINT fk_leads_masterclass FOREIGN KEY (masterclass_id) REFERENCES masterclasses (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- lead_interactions (histórico append-only de cada envío de formulario)
-- ---------------------------------------------------------------------------
CREATE TABLE lead_interactions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    lead_id BIGINT UNSIGNED NOT NULL,
    masterclass_id BIGINT UNSIGNED NULL,
    source VARCHAR(50) NOT NULL DEFAULT 'syllabus_form',
    campaign VARCHAR(100) NULL,
    utm_source VARCHAR(100) NULL,
    utm_medium VARCHAR(100) NULL,
    utm_campaign VARCHAR(150) NULL,
    utm_content VARCHAR(150) NULL,
    utm_term VARCHAR(150) NULL,
    ip_address VARBINARY(16) NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_lead_interactions_lead_id (lead_id),
    KEY idx_lead_interactions_masterclass_id (masterclass_id),
    KEY idx_lead_interactions_created_at (created_at),
    CONSTRAINT fk_lead_interactions_lead FOREIGN KEY (lead_id) REFERENCES leads (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_lead_interactions_masterclass FOREIGN KEY (masterclass_id) REFERENCES masterclasses (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- masterclass_materials
-- ---------------------------------------------------------------------------
CREATE TABLE masterclass_materials (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    masterclass_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    type ENUM('pdf', 'link', 'video') NOT NULL DEFAULT 'pdf',
    file_path VARCHAR(255) NULL,
    external_url TEXT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    is_published TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_materials_masterclass_id (masterclass_id),
    CONSTRAINT fk_materials_masterclass FOREIGN KEY (masterclass_id) REFERENCES masterclasses (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- email_templates
-- ---------------------------------------------------------------------------
CREATE TABLE email_templates (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug VARCHAR(80) NOT NULL,
    name VARCHAR(150) NOT NULL,
    sendgrid_template_id VARCHAR(50) NULL,
    subject VARCHAR(255) NOT NULL,
    body_html MEDIUMTEXT NULL,
    variables JSON NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_email_templates_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- email_queue
-- ---------------------------------------------------------------------------
CREATE TABLE email_queue (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    template_slug VARCHAR(80) NOT NULL,
    to_email VARCHAR(255) NOT NULL,
    to_name VARCHAR(150) NULL,
    payload JSON NULL,
    scheduled_at DATETIME NOT NULL,
    sent_at DATETIME NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    last_error TEXT NULL,
    status ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_email_queue_status_scheduled (status, scheduled_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- email_logs
-- ---------------------------------------------------------------------------
CREATE TABLE email_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NULL,
    template_slug VARCHAR(80) NOT NULL,
    to_email VARCHAR(255) NOT NULL,
    sendgrid_message_id VARCHAR(100) NULL,
    status VARCHAR(50) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_email_logs_user_id (user_id),
    KEY idx_email_logs_created_at (created_at),
    CONSTRAINT fk_email_logs_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- password_reset_tokens
-- ---------------------------------------------------------------------------
CREATE TABLE password_reset_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_password_reset_token_hash (token_hash),
    KEY idx_password_reset_user_id (user_id),
    CONSTRAINT fk_password_reset_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- email_verification_tokens
-- ---------------------------------------------------------------------------
CREATE TABLE email_verification_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_email_verification_token_hash (token_hash),
    KEY idx_email_verification_user_id (user_id),
    CONSTRAINT fk_email_verification_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- remember_tokens ("recuérdame" — patrón selector/validador, revocable y rotativo)
-- ---------------------------------------------------------------------------
CREATE TABLE remember_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    selector CHAR(24) NOT NULL,
    validator_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_remember_tokens_selector (selector),
    KEY idx_remember_tokens_user_id (user_id),
    CONSTRAINT fk_remember_tokens_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- audit_logs
-- ---------------------------------------------------------------------------
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    actor_user_id BIGINT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id BIGINT UNSIGNED NULL,
    ip_address VARBINARY(16) NULL,
    metadata JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_logs_actor (actor_user_id),
    KEY idx_audit_logs_entity (entity_type, entity_id),
    KEY idx_audit_logs_created_at (created_at),
    CONSTRAINT fk_audit_logs_user FOREIGN KEY (actor_user_id) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- settings
-- ---------------------------------------------------------------------------
CREATE TABLE settings (
    `key` VARCHAR(100) NOT NULL,
    value TEXT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- rate_limits (rate limiting básico sin dependencias externas — Hostinger friendly)
-- ---------------------------------------------------------------------------
CREATE TABLE rate_limits (
    `key` VARCHAR(191) NOT NULL,
    attempts INT UNSIGNED NOT NULL DEFAULT 1,
    window_start DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- SEED DATA
-- ---------------------------------------------------------------------------

-- Admin inicial — CAMBIAR CONTRASEÑA EN PRODUCCIÓN
-- Contraseña temporal de desarrollo: password
-- Generar nuevo hash: php -r "echo password_hash('TuClaveSegura', PASSWORD_DEFAULT);"
INSERT INTO users (uuid, name, email, password_hash, role, email_verified_at, privacy_accepted_at, created_at, updated_at)
VALUES (
    'a0000000-0000-4000-8000-000000000001',
    'Administrador',
    'admin@likantor.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'super_admin',
    NOW(),
    NOW(),
    NOW(),
    NOW()
);

-- Primera Masterclass
-- 17 sep 2026 18:30 America/Mexico_City = 18 sep 2026 00:30 UTC
INSERT INTO masterclasses (
    slug, name, subtitle, description, instructor_name, instructor_bio,
    event_starts_at, timezone, duration_minutes, price, currency, modality,
    published_at, status, disclaimer_text, created_at, updated_at
) VALUES (
    'revision-estructuras-post-sismo',
    'Revisión de Estructuras Post-Sismo',
    'Evaluación visual y conceptual después de un sismo',
    'Masterclass educativa para aprender a evaluar visual y conceptualmente una estructura después de un sismo, identificar señales de posible daño, saber cuándo evacuar y comprender qué aspectos debe revisar un profesional.',
    'Fernando Robledo',
    NULL,
    '2026-09-18 00:30:00',
    'America/Mexico_City',
    180,
    65.00,
    'USD',
    'online_zoom',
    NOW(),
    'published',
    'Esta Masterclass es contenido educativo y NO sustituye una evaluación profesional de seguridad estructural. Ante daños visibles, dudas sobre estabilidad o instrucciones de autoridades, consulte de inmediato a un profesional competente.',
    NOW(),
    NOW()
);

-- Email templates base
-- El template "syllabus_request" incluye un body_html funcional que puede sustituirse
-- posteriormente por el PDF/contenido definitivo del temario (editar body_html o asignar
-- sendgrid_template_id para usar un Dynamic Template de SendGrid en su lugar).
INSERT INTO email_templates (slug, name, subject, body_html, variables, is_active) VALUES
('syllabus_request', 'Temario / información de la Masterclass', 'Tu información — {{masterclass_name}}',
'<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>{{masterclass_name}}</title></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 0;">
<tr><td align="center">
<table role="presentation" width="100%" style="max-width:560px;background:#ffffff;border-radius:8px;overflow:hidden;">
<tr><td style="background:#0f1724;padding:24px 32px;">
<span style="color:#c9a227;font-size:20px;font-weight:bold;letter-spacing:1px;">LIKANTOR</span>
</td></tr>
<tr><td style="padding:32px;">
<h1 style="margin:0 0 16px;font-size:22px;color:#0f1724;">Hola {{name}},</h1>
<p style="margin:0 0 16px;font-size:15px;color:#334155;line-height:1.6;">
Gracias por tu interes en la Masterclass <strong>{{masterclass_name}}</strong>. A continuacion encontraras los datos principales del evento.
</p>
<table role="presentation" width="100%" style="background:#f1f5f9;border-radius:8px;margin:0 0 20px;">
<tr><td style="padding:16px 20px;font-size:14px;color:#334155;">
<strong>Fecha:</strong> {{event_date}}<br>
<strong>Hora:</strong> {{event_time}}<br>
<strong>Modalidad:</strong> En vivo por Zoom
</td></tr>
</table>
<p style="margin:0 0 24px;font-size:15px;color:#334155;line-height:1.6;">
En breve compartiremos el temario detallado. Mientras tanto, conoce todos los detalles de la Masterclass y reserva tu lugar:
</p>
<table role="presentation" cellpadding="0" cellspacing="0">
<tr><td style="border-radius:6px;background:#c9a227;">
<a href="{{cta_url}}" style="display:inline-block;padding:14px 28px;color:#0f1724;font-weight:bold;text-decoration:none;font-size:15px;">Ver la Masterclass</a>
</td></tr>
</table>
<p style="margin:24px 0 0;font-size:12px;color:#94a3b8;line-height:1.5;">
Esta Masterclass tiene fines educativos e informativos y no sustituye una evaluacion profesional de seguridad estructural.
</p>
</td></tr>
<tr><td style="padding:20px 32px;background:#f8fafc;font-size:12px;color:#94a3b8;">
Likantor — Ingenieria en Estructuras · Guadalajara, Jalisco, Mexico
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>',
'["name","masterclass_name","event_date","event_time","cta_url"]', 1),
('welcome_registration', 'Bienvenida registro', 'Bienvenido a Likantor Masterclasses', NULL, '["user_name"]', 1),
('masterclass_access', 'Acceso Masterclass', 'Tu acceso a {{masterclass_name}}', NULL, '["user_name","masterclass_name","dashboard_url"]', 1),
('event_reminder_24h', 'Recordatorio 24h', 'Mañana: {{masterclass_name}}', NULL, '["user_name","masterclass_name","event_time"]', 1),
('event_reminder_1h', 'Recordatorio 1h', 'En 1 hora: {{masterclass_name}}', NULL, '["user_name","masterclass_name","event_time"]', 1),
('event_pre_info', 'Info previa evento', 'Información previa — {{masterclass_name}}', NULL, '["user_name","masterclass_name"]', 1),
('event_post_thanks', 'Gracias por participar', 'Gracias por participar — {{masterclass_name}}', NULL, '["user_name","masterclass_name"]', 1),
('admin_new_sale', 'Nueva venta admin', 'Nueva inscripción — {{masterclass_name}}', NULL, '["masterclass_name","user_name","amount"]', 1);

-- Verificación de email (registro de usuario)
INSERT INTO email_templates (slug, name, subject, body_html, variables, is_active) VALUES
('email_verification', 'Verificación de correo', 'Confirma tu correo — Likantor Masterclasses',
'<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Verifica tu correo</title></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 0;">
<tr><td align="center">
<table role="presentation" width="100%" style="max-width:560px;background:#ffffff;border-radius:8px;overflow:hidden;">
<tr><td style="background:#0f1724;padding:24px 32px;">
<span style="color:#c9a227;font-size:20px;font-weight:bold;letter-spacing:1px;">LIKANTOR</span>
</td></tr>
<tr><td style="padding:32px;">
<h1 style="margin:0 0 16px;font-size:22px;color:#0f1724;">Hola {{name}},</h1>
<p style="margin:0 0 20px;font-size:15px;color:#334155;line-height:1.6;">
Gracias por crear tu cuenta en Likantor Masterclasses. Confirma tu correo electronico para activar tu acceso:
</p>
<table role="presentation" cellpadding="0" cellspacing="0">
<tr><td style="border-radius:6px;background:#c9a227;">
<a href="{{verify_url}}" style="display:inline-block;padding:14px 28px;color:#0f1724;font-weight:bold;text-decoration:none;font-size:15px;">Verificar mi correo</a>
</td></tr>
</table>
<p style="margin:24px 0 0;font-size:13px;color:#94a3b8;line-height:1.5;">
Este enlace es de un solo uso y expira en {{expires_in}}. Si tu no creaste esta cuenta, puedes ignorar este mensaje.
</p>
</td></tr>
<tr><td style="padding:20px 32px;background:#f8fafc;font-size:12px;color:#94a3b8;">
Likantor — Ingenieria en Estructuras · Guadalajara, Jalisco, Mexico
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>',
'["name","verify_url","expires_in"]', 1);

-- Recuperación de contraseña
INSERT INTO email_templates (slug, name, subject, body_html, variables, is_active) VALUES
('password_reset', 'Recuperar contraseña', 'Restablece tu contraseña — Likantor Masterclasses',
'<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Restablece tu contraseña</title></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 0;">
<tr><td align="center">
<table role="presentation" width="100%" style="max-width:560px;background:#ffffff;border-radius:8px;overflow:hidden;">
<tr><td style="background:#0f1724;padding:24px 32px;">
<span style="color:#c9a227;font-size:20px;font-weight:bold;letter-spacing:1px;">LIKANTOR</span>
</td></tr>
<tr><td style="padding:32px;">
<h1 style="margin:0 0 16px;font-size:22px;color:#0f1724;">Hola {{name}},</h1>
<p style="margin:0 0 20px;font-size:15px;color:#334155;line-height:1.6;">
Recibimos una solicitud para restablecer la contraseña de tu cuenta. Si fuiste tu, define una nueva contraseña aqui:
</p>
<table role="presentation" cellpadding="0" cellspacing="0">
<tr><td style="border-radius:6px;background:#c9a227;">
<a href="{{reset_url}}" style="display:inline-block;padding:14px 28px;color:#0f1724;font-weight:bold;text-decoration:none;font-size:15px;">Restablecer contraseña</a>
</td></tr>
</table>
<p style="margin:24px 0 0;font-size:13px;color:#94a3b8;line-height:1.5;">
Este enlace es de un solo uso y expira en {{expires_in}}. Si tu no solicitaste este cambio, ignora este mensaje;
tu contraseña actual seguira funcionando con normalidad.
</p>
</td></tr>
<tr><td style="padding:20px 32px;background:#f8fafc;font-size:12px;color:#94a3b8;">
Likantor — Ingenieria en Estructuras · Guadalajara, Jalisco, Mexico
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>',
'["name","reset_url","expires_in"]', 1);

-- Pago confirmado / lugar reservado
INSERT INTO email_templates (slug, name, subject, body_html, variables, is_active) VALUES
('payment_confirmed', 'Pago confirmado', 'Pago confirmado — {{masterclass_name}}',
'<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Pago confirmado</title></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 0;">
<tr><td align="center">
<table role="presentation" width="100%" style="max-width:560px;background:#ffffff;border-radius:8px;overflow:hidden;">
<tr><td style="background:#0f1724;padding:24px 32px;">
<span style="color:#c9a227;font-size:20px;font-weight:bold;letter-spacing:1px;">LIKANTOR</span>
</td></tr>
<tr><td style="padding:32px;">
<h1 style="margin:0 0 16px;font-size:22px;color:#0f1724;">¡Listo, {{name}}!</h1>
<p style="margin:0 0 20px;font-size:15px;color:#334155;line-height:1.6;">
Tu pago fue confirmado y tu lugar en <strong>{{masterclass_name}}</strong> quedo reservado.
</p>
<table role="presentation" width="100%" style="background:#f1f5f9;border-radius:8px;margin:0 0 20px;">
<tr><td style="padding:16px 20px;font-size:14px;color:#334155;">
<strong>Fecha:</strong> {{event_date}}<br>
<strong>Hora:</strong> {{event_time}}<br>
<strong>Monto pagado:</strong> {{amount}} {{currency}}<br>
<strong>Folio:</strong> {{folio}}
</td></tr>
</table>
<p style="margin:0 0 24px;font-size:15px;color:#334155;line-height:1.6;">
Ingresa a tu cuenta para ver el acceso a la Masterclass (incluyendo el enlace de Zoom, disponible antes del evento):
</p>
<table role="presentation" cellpadding="0" cellspacing="0">
<tr><td style="border-radius:6px;background:#c9a227;">
<a href="{{login_url}}" style="display:inline-block;padding:14px 28px;color:#0f1724;font-weight:bold;text-decoration:none;font-size:15px;">Iniciar sesion</a>
</td></tr>
</table>
<p style="margin:24px 0 0;font-size:12px;color:#94a3b8;line-height:1.5;">
Conserva este correo como comprobante de tu inscripcion. Si tienes dudas sobre tu pago, contactanos indicando tu folio.
</p>
</td></tr>
<tr><td style="padding:20px 32px;background:#f8fafc;font-size:12px;color:#94a3b8;">
Likantor — Ingenieria en Estructuras · Guadalajara, Jalisco, Mexico
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>',
'["name","masterclass_name","event_date","event_time","amount","currency","folio","login_url"]', 1);

SET FOREIGN_KEY_CHECKS = 1;
