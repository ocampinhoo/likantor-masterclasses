<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $config = Config::get('database');

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['name'],
            $config['charset']
        );

        try {
            self::$connection = new PDO(
                $dsn,
                $config['user'],
                $config['password'],
                $config['options']
            );
        } catch (PDOException $e) {
            throw new \RuntimeException('Database connection failed.', 0, $e);
        }

        return self::$connection;
    }
}
