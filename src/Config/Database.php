<?php

declare(strict_types=1);

namespace App\Config;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $env = require dirname(__DIR__, 2) . '/config/env.php';
        $dsn = (string) $env('DB_DSN', 'pgsql:host=localhost;port=5432;dbname=cherynes');
        $user = (string) $env('DB_USER', '');
        $pass = (string) $env('DB_PASS', '');

        try {
            self::$connection = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $exception) {
            throw new RuntimeException('Database connection failed. Check DB_DSN, DB_USER, and DB_PASS.', 0, $exception);
        }

        return self::$connection;
    }
}
