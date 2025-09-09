<?php
declare(strict_types=1);

namespace App;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            try {
                self::$pdo = new PDO(
                    Config::dbDsn(),
                    Config::dbUser(),
                    Config::dbPass(),
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
            } catch (PDOException $e) {
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'error' => 'DB connection failed']);
                exit;
            }
        }
        return self::$pdo;
    }
}
