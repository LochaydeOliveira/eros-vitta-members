<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Http\JsonResponse;
use App\Security\Jwt;
use App\Security\RateLimiter;
use PDO;

final class AdminAuthController
{
    public static function login(array $body): void
    {
        // Rate limiting por IP + email
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $emailKey = strtolower(trim((string)($body['email'] ?? '')));
        $limiter = new RateLimiter('admin_login:' . $ip . ':' . $emailKey, 10, 60);
        if (!$limiter->allow()) {
            JsonResponse::error('Muitas tentativas. Tente novamente em instantes.', 429);
            return;
        }
        $email = strtolower(trim((string)($body['email'] ?? '')));
        $senha = (string)($body['senha'] ?? '');
        if ($email === '' || $senha === '') {
            JsonResponse::error('Email e senha são obrigatórios', 422);
            return;
        }
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id, senha_hash, ativo FROM admins WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || !password_verify($senha, (string)$row['senha_hash'])) {
            JsonResponse::error('Credenciais inválidas', 401);
            return;
        }
        if ((int)$row['ativo'] !== 1) {
            JsonResponse::error('Admin inativo', 403);
            return;
        }
        $token = Jwt::sign(['sub' => (int)$row['id'], 'email' => $email, 'role' => 'admin']);
        JsonResponse::ok(['token' => $token]);
    }
}
