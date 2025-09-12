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
    /**
     * Criar admin de backup
     */
    public static function createBackup(array $body): void
    {
        $pdo = Database::pdo();
        
        // Verificar se já existe admin de backup
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM admins WHERE email = ?');
        $stmt->execute(['backup@erosvitta.com.br']);
        if ((int)$stmt->fetchColumn() > 0) {
            JsonResponse::error('Admin de backup já existe', 400);
            return;
        }
        
        // Gerar senha aleatória
        $senhaPlain = bin2hex(random_bytes(8)); // 16 caracteres
        $senhaHash = password_hash($senhaPlain, PASSWORD_BCRYPT);
        
        // Criar admin de backup
        $stmt = $pdo->prepare('INSERT INTO admins (nome, email, senha_hash, ativo, criado_em, atualizado_em) VALUES (?, ?, ?, 1, NOW(), NOW())');
        $stmt->execute(['Admin Backup', 'backup@erosvitta.com.br', $senhaHash]);
        
        JsonResponse::ok([
            'success' => true,
            'email' => 'backup@erosvitta.com.br',
            'senha' => $senhaPlain,
            'message' => 'Admin de backup criado com sucesso. Guarde essas credenciais em local seguro!'
        ]);
    }

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
