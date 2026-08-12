-- Migración 002 — Tracking de leads (UTM/campaña), deduplicación e interacciones,
-- rate limiting básico y template de temario.
-- Ejecutar solo si la base de datos ya existía con el schema.sql anterior
-- (una instalación nueva ya incluye todo esto directamente en schema.sql).

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Nuevas columnas de tracking en leads
ALTER TABLE leads
    ADD COLUMN campaign VARCHAR(100) NULL AFTER source,
    ADD COLUMN utm_source VARCHAR(100) NULL AFTER campaign,
    ADD COLUMN utm_medium VARCHAR(100) NULL AFTER utm_source,
    ADD COLUMN utm_campaign VARCHAR(150) NULL AFTER utm_medium,
    ADD COLUMN utm_content VARCHAR(150) NULL AFTER utm_campaign,
    ADD COLUMN utm_term VARCHAR(150) NULL AFTER utm_content,
    ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- 2. Deduplicar emails existentes antes de crear el índice único
--    (conserva el registro más reciente por email; ajustar si se requiere otra estrategia)
DELETE l1 FROM leads l1
INNER JOIN leads l2
    ON l1.email = l2.email AND l1.id < l2.id;

ALTER TABLE leads
    DROP INDEX idx_leads_email,
    ADD UNIQUE KEY uk_leads_email (email),
    ADD KEY idx_leads_utm_campaign (utm_campaign);

-- 3. Historial de interacciones (append-only)
CREATE TABLE IF NOT EXISTS lead_interactions (
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

-- 4. Rate limiting básico (sin Redis, compatible con Hostinger)
CREATE TABLE IF NOT EXISTS rate_limits (
    `key` VARCHAR(191) NOT NULL,
    attempts INT UNSIGNED NOT NULL DEFAULT 1,
    window_start DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Template funcional de "Temario / información de la Masterclass"
UPDATE email_templates
SET
    name = 'Temario / información de la Masterclass',
    subject = 'Tu información — {{masterclass_name}}',
    body_html = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>{{masterclass_name}}</title></head><body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 0;"><tr><td align="center"><table role="presentation" width="100%" style="max-width:560px;background:#ffffff;border-radius:8px;overflow:hidden;"><tr><td style="background:#0f1724;padding:24px 32px;"><span style="color:#c9a227;font-size:20px;font-weight:bold;letter-spacing:1px;">LIKANTOR</span></td></tr><tr><td style="padding:32px;"><h1 style="margin:0 0 16px;font-size:22px;color:#0f1724;">Hola {{name}},</h1><p style="margin:0 0 16px;font-size:15px;color:#334155;line-height:1.6;">Gracias por tu interes en la Masterclass <strong>{{masterclass_name}}</strong>. A continuacion encontraras los datos principales del evento.</p><table role="presentation" width="100%" style="background:#f1f5f9;border-radius:8px;margin:0 0 20px;"><tr><td style="padding:16px 20px;font-size:14px;color:#334155;"><strong>Fecha:</strong> {{event_date}}<br><strong>Hora:</strong> {{event_time}}<br><strong>Modalidad:</strong> En vivo por Zoom</td></tr></table><p style="margin:0 0 24px;font-size:15px;color:#334155;line-height:1.6;">En breve compartiremos el temario detallado. Mientras tanto, conoce todos los detalles de la Masterclass y reserva tu lugar:</p><table role="presentation" cellpadding="0" cellspacing="0"><tr><td style="border-radius:6px;background:#c9a227;"><a href="{{cta_url}}" style="display:inline-block;padding:14px 28px;color:#0f1724;font-weight:bold;text-decoration:none;font-size:15px;">Ver la Masterclass</a></td></tr></table><p style="margin:24px 0 0;font-size:12px;color:#94a3b8;line-height:1.5;">Esta Masterclass tiene fines educativos e informativos y no sustituye una evaluacion profesional de seguridad estructural.</p></td></tr><tr><td style="padding:20px 32px;background:#f8fafc;font-size:12px;color:#94a3b8;">Likantor — Ingenieria en Estructuras · Guadalajara, Jalisco, Mexico</td></tr></table></td></tr></table></body></html>',
    variables = '["name","masterclass_name","event_date","event_time","cta_url"]'
WHERE slug = 'syllabus_request';

SET FOREIGN_KEY_CHECKS = 1;
