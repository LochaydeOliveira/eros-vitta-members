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
                // Log detalhado da falha de conexão
                try {
                    $dir = __DIR__ . '/../logs';
                    if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
                    $line = sprintf(
                        "%s\tDB_CONNECT_FAIL\t%s\t%s\n",
                        date('c'),
                        Config::dbDsn(),
                        $e->getMessage()
                    );
                    @file_put_contents($dir . '/api_' . date('Y-m-d') . '.log', $line, FILE_APPEND);
                } catch (\Throwable $_) { /* ignore */ }
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'error' => 'DB connection failed']);
                exit;
            }
        }
        return self::$pdo;
    }
}
