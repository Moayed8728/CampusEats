<?php

namespace App\Database;

use PDO;
use PDOException;

class Database
{
    public static function connect(): PDO
    {
        $host = $_ENV['localhost'];
        $db   = $_ENV['campuseats'];
        $user = $_ENV['root'];
        $pass = $_ENV['root'];
        $port = $_ENV['3306'];

        try {
            return new PDO(
                "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                "error" => "Database connection failed",
                "details" => $e->getMessage()
            ]);
            exit;
        }
    }
}