<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config;
use App\Http\JsonResponse;
use PDO;
use PDOException;

final class DiagController
{
    public static function db(array $body): void
    {
        $key = (string)($body['key'] ?? '');
        if ($key === '' || $key !== Config::appKey()) {
            JsonResponse::error('Não autorizado', 403);
            return;
        }
        $dsn = Config::dbDsn();
        $user = Config::dbUser();
        $pass = Config::dbPass();
        try {
            $pdo = new PDO(
                $dsn,
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
            $ver = (string)$pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
            $pdo->query('SELECT 1');
            JsonResponse::ok([
                'connected' => true,
                'server_version' => $ver,
            ]);
        } catch (PDOException $e) {
            JsonResponse::error('DB connect error', 500, [
                'message' => $e->getMessage(),
                'code' => (int)$e->getCode(),
                'dsn' => $dsn,
                'user' => $user,
            ]);
        }
    }
}


