<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config;
use App\Http\JsonResponse;
use App\Database;
use PDO;
use PDOException;

final class DiagController
{
    public static function hash(array $body): void
    {
        $key = (string)($body['key'] ?? '');
        if ($key === '' || $key !== Config::appKey()) {
            JsonResponse::error('Não autorizado', 403);
            return;
        }
        $senha = (string)($body['senha'] ?? '');
        if ($senha === '') {
            JsonResponse::error('Senha é obrigatória', 422);
            return;
        }
        $hash = password_hash($senha, PASSWORD_BCRYPT);
        JsonResponse::ok(['bcrypt' => $hash]);
    }

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

    public static function adminAuth(array $body): void
    {
        $key = (string)($body['key'] ?? '');
        if ($key === '' || $key !== Config::appKey()) {
            JsonResponse::error('Não autorizado', 403);
            return;
        }
        $email = strtolower(trim((string)($body['email'] ?? '')));
        $senha = (string)($body['senha'] ?? '');
        if ($email === '' || $senha === '') {
            JsonResponse::error('email e senha são obrigatórios', 422);
            return;
        }
        try {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare('SELECT id, senha_hash, ativo FROM admins WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                JsonResponse::ok(['found' => false]);
                return;
            }
            $hash = (string)($row['senha_hash'] ?? '');
            $ativo = (int)($row['ativo'] ?? 0);
            $matches = ($hash !== '') ? password_verify($senha, $hash) : false;
            JsonResponse::ok([
                'found' => true,
                'ativo' => $ativo === 1,
                'hash_present' => $hash !== '',
                'password_matches' => $matches,
            ]);
        } catch (PDOException $e) {
            JsonResponse::error('Erro DB', 500, ['message' => $e->getMessage()]);
        }
    }
}


