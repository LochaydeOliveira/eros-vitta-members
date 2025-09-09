<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Http\JsonResponse;
use App\Security\Jwt;
use PDO;

final class AdminAuthController
{
    public static function login(array $body): void
    {
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
