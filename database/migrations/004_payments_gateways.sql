-- Migración 004 — Sistema de pagos (Stripe + Mercado Pago)
-- Ejecutar solo si la base de datos ya existía con el schema.sql anterior.

SET FOREIGN_KEY_CHECKS = 0;

-- 1. payments: nuevos estados canónicos y enrollment_id ahora es NULL-able
--    (la inscripción solo se crea cuando el pago queda 'approved').
ALTER TABLE payments
    DROP FOREIGN KEY fk_payments_enrollment;

ALTER TABLE payments
    MODIFY enrollment_id BIGINT UNSIGNED NULL,
    MODIFY status ENUM('pending', 'approved', 'failed', 'cancelled', 'refunded', 'chargeback', 'unknown') NOT NULL DEFAULT 'pending',
    MODIFY amount DECIMAL(10, 2) NOT NULL COMMENT 'Monto solicitado/recibido en el checkout de este proveedor (ver metadata.commercial_price para el precio comercial)';

ALTER TABLE payments
    ADD CONSTRAINT fk_payments_enrollment FOREIGN KEY (enrollment_id) REFERENCES enrollments (id) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE payments
    ADD KEY idx_payments_enrollment_id (enrollment_id);

-- 2. payment_events: columnas para idempotencia por id del proveedor y auditoría
ALTER TABLE payment_events
    ADD COLUMN provider_event_id VARCHAR(255) NULL AFTER event_type,
    ADD COLUMN payload MEDIUMTEXT NULL COMMENT 'Cuerpo crudo del evento, para auditoría/reproceso manual' AFTER payload_hash,
    ADD COLUMN processed_at DATETIME NULL AFTER error_message,
    ADD KEY idx_payment_events_provider_event_id (provider, provider_event_id);

-- 3. Template de email "Pago confirmado" con contenido funcional
INSERT INTO email_templates (slug, name, subject, body_html, variables, is_active)
VALUES (
    'payment_confirmed', 'Pago confirmado', 'Pago confirmado — {{masterclass_name}}',
    '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Pago confirmado</title></head><body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 0;"><tr><td align="center"><table role="presentation" width="100%" style="max-width:560px;background:#ffffff;border-radius:8px;overflow:hidden;"><tr><td style="background:#0f1724;padding:24px 32px;"><span style="color:#c9a227;font-size:20px;font-weight:bold;letter-spacing:1px;">LIKANTOR</span></td></tr><tr><td style="padding:32px;"><h1 style="margin:0 0 16px;font-size:22px;color:#0f1724;">¡Listo, {{name}}!</h1><p style="margin:0 0 20px;font-size:15px;color:#334155;line-height:1.6;">Tu pago fue confirmado y tu lugar en <strong>{{masterclass_name}}</strong> quedo reservado.</p><table role="presentation" width="100%" style="background:#f1f5f9;border-radius:8px;margin:0 0 20px;"><tr><td style="padding:16px 20px;font-size:14px;color:#334155;"><strong>Fecha:</strong> {{event_date}}<br><strong>Hora:</strong> {{event_time}}<br><strong>Monto pagado:</strong> {{amount}} {{currency}}<br><strong>Folio:</strong> {{folio}}</td></tr></table><p style="margin:0 0 24px;font-size:15px;color:#334155;line-height:1.6;">Ingresa a tu cuenta para ver el acceso a la Masterclass (incluyendo el enlace de Zoom, disponible antes del evento):</p><table role="presentation" cellpadding="0" cellspacing="0"><tr><td style="border-radius:6px;background:#c9a227;"><a href="{{login_url}}" style="display:inline-block;padding:14px 28px;color:#0f1724;font-weight:bold;text-decoration:none;font-size:15px;">Iniciar sesion</a></td></tr></table><p style="margin:24px 0 0;font-size:12px;color:#94a3b8;line-height:1.5;">Conserva este correo como comprobante de tu inscripcion. Si tienes dudas sobre tu pago, contactanos indicando tu folio.</p></td></tr><tr><td style="padding:20px 32px;background:#f8fafc;font-size:12px;color:#94a3b8;">Likantor — Ingenieria en Estructuras · Guadalajara, Jalisco, Mexico</td></tr></table></td></tr></table></body></html>',
    '["name","masterclass_name","event_date","event_time","amount","currency","folio","login_url"]', 1
)
ON DUPLICATE KEY UPDATE
    subject = VALUES(subject),
    body_html = VALUES(body_html),
    variables = VALUES(variables);

SET FOREIGN_KEY_CHECKS = 1;
