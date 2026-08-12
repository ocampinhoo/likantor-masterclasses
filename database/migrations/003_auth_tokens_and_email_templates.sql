-- Migración 003 — Registro/login seguro: verificación de email, recuperación de
-- contraseña y "recuérdame" persistente.
-- Ejecutar solo si la base de datos ya existía con el schema.sql anterior.

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Índice único en password_reset_tokens (lookup directo por hash)
ALTER TABLE password_reset_tokens
    DROP INDEX idx_password_reset_token_hash,
    ADD UNIQUE KEY uk_password_reset_token_hash (token_hash);

-- 2. Tokens de verificación de email
CREATE TABLE IF NOT EXISTS email_verification_tokens (
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

-- 3. Tokens de "recuérdame" (selector/validador, revocables y rotativos)
CREATE TABLE IF NOT EXISTS remember_tokens (
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

-- 4. Templates de email (verificación / recuperación) con contenido funcional
INSERT INTO email_templates (slug, name, subject, body_html, variables, is_active)
VALUES (
    'email_verification', 'Verificación de correo', 'Confirma tu correo — Likantor Masterclasses',
    '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Verifica tu correo</title></head><body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 0;"><tr><td align="center"><table role="presentation" width="100%" style="max-width:560px;background:#ffffff;border-radius:8px;overflow:hidden;"><tr><td style="background:#0f1724;padding:24px 32px;"><span style="color:#c9a227;font-size:20px;font-weight:bold;letter-spacing:1px;">LIKANTOR</span></td></tr><tr><td style="padding:32px;"><h1 style="margin:0 0 16px;font-size:22px;color:#0f1724;">Hola {{name}},</h1><p style="margin:0 0 20px;font-size:15px;color:#334155;line-height:1.6;">Gracias por crear tu cuenta en Likantor Masterclasses. Confirma tu correo electronico para activar tu acceso:</p><table role="presentation" cellpadding="0" cellspacing="0"><tr><td style="border-radius:6px;background:#c9a227;"><a href="{{verify_url}}" style="display:inline-block;padding:14px 28px;color:#0f1724;font-weight:bold;text-decoration:none;font-size:15px;">Verificar mi correo</a></td></tr></table><p style="margin:24px 0 0;font-size:13px;color:#94a3b8;line-height:1.5;">Este enlace es de un solo uso y expira en {{expires_in}}. Si tu no creaste esta cuenta, puedes ignorar este mensaje.</p></td></tr><tr><td style="padding:20px 32px;background:#f8fafc;font-size:12px;color:#94a3b8;">Likantor — Ingenieria en Estructuras · Guadalajara, Jalisco, Mexico</td></tr></table></td></tr></table></body></html>',
    '["name","verify_url","expires_in"]', 1
)
ON DUPLICATE KEY UPDATE
    subject = VALUES(subject),
    body_html = VALUES(body_html),
    variables = VALUES(variables);

INSERT INTO email_templates (slug, name, subject, body_html, variables, is_active)
VALUES (
    'password_reset', 'Recuperar contraseña', 'Restablece tu contraseña — Likantor Masterclasses',
    '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Restablece tu contraseña</title></head><body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 0;"><tr><td align="center"><table role="presentation" width="100%" style="max-width:560px;background:#ffffff;border-radius:8px;overflow:hidden;"><tr><td style="background:#0f1724;padding:24px 32px;"><span style="color:#c9a227;font-size:20px;font-weight:bold;letter-spacing:1px;">LIKANTOR</span></td></tr><tr><td style="padding:32px;"><h1 style="margin:0 0 16px;font-size:22px;color:#0f1724;">Hola {{name}},</h1><p style="margin:0 0 20px;font-size:15px;color:#334155;line-height:1.6;">Recibimos una solicitud para restablecer la contraseña de tu cuenta. Si fuiste tu, define una nueva contraseña aqui:</p><table role="presentation" cellpadding="0" cellspacing="0"><tr><td style="border-radius:6px;background:#c9a227;"><a href="{{reset_url}}" style="display:inline-block;padding:14px 28px;color:#0f1724;font-weight:bold;text-decoration:none;font-size:15px;">Restablecer contraseña</a></td></tr></table><p style="margin:24px 0 0;font-size:13px;color:#94a3b8;line-height:1.5;">Este enlace es de un solo uso y expira en {{expires_in}}. Si tu no solicitaste este cambio, ignora este mensaje; tu contraseña actual seguira funcionando con normalidad.</p></td></tr><tr><td style="padding:20px 32px;background:#f8fafc;font-size:12px;color:#94a3b8;">Likantor — Ingenieria en Estructuras · Guadalajara, Jalisco, Mexico</td></tr></table></td></tr></table></body></html>',
    '["name","reset_url","expires_in"]', 1
)
ON DUPLICATE KEY UPDATE
    subject = VALUES(subject),
    body_html = VALUES(body_html),
    variables = VALUES(variables);

SET FOREIGN_KEY_CHECKS = 1;
