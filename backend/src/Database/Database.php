<?php

namespace App\Database;

use PDO;

class Database
{
    public static function connect(): PDO
    {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        if (filter_var($_ENV['DB_SSL'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        }

        return new PDO(
            sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $_ENV['DB_HOST'],
                $_ENV['DB_PORT'],
                $_ENV['DB_NAME']
            ),
            $_ENV['DB_USER'],
            $_ENV['DB_PASS'],
            $options
        );
    }
}
