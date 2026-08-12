<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Rate limiting básico persistido en MySQL (sin Redis), apto para hosting compartido.
 */
final class RateLimiter
{
    public static function tooManyAttempts(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        if (!Config::security('rate_limit_enabled', true)) {
            return false;
        }

        $normalizedKey = substr($key, 0, 191);

        try {
            $db = Database::connection();

            $select = $db->prepare('SELECT attempts, window_start FROM rate_limits WHERE `key` = :key LIMIT 1');
            $select->execute(['key' => $normalizedKey]);
            $row = $select->fetch();

            if ($row === false) {
                $insert = $db->prepare('INSERT INTO rate_limits (`key`, attempts, window_start) VALUES (:key, 1, NOW())');
                $insert->execute(['key' => $normalizedKey]);

                return false;
            }

            $windowStart = strtotime((string) $row['window_start']) ?: time();

            if ((time() - $windowStart) > $decaySeconds) {
                $reset = $db->prepare('UPDATE rate_limits SET attempts = 1, window_start = NOW() WHERE `key` = :key');
                $reset->execute(['key' => $normalizedKey]);

                return false;
            }

            if ((int) $row['attempts'] >= $maxAttempts) {
                return true;
            }

            $increment = $db->prepare('UPDATE rate_limits SET attempts = attempts + 1 WHERE `key` = :key');
            $increment->execute(['key' => $normalizedKey]);

            return false;
        } catch (\Throwable $e) {
            // Si la BD no está disponible, no bloqueamos al usuario legítimo.
            ErrorHandler::log('RateLimiter error', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public static function reset(string $key): void
    {
        try {
            $db = Database::connection();
            $stmt = $db->prepare('DELETE FROM rate_limits WHERE `key` = :key');
            $stmt->execute(['key' => substr($key, 0, 191)]);
        } catch (\Throwable) {
            // Ignorar: el reseteo es best-effort.
        }
    }
}
