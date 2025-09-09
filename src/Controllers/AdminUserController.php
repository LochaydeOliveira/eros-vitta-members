<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Http\JsonResponse;
use PDO;

final class AdminUserController
{
    public static function list(): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->query('SELECT id, nome, email, status, criado_em, atualizado_em, ultimo_login_em FROM usuarios ORDER BY id DESC');
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        JsonResponse::ok(['items' => $items]);
    }

    public static function block(array $body): void
    {
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) { JsonResponse::error('id obrigatório', 422); return; }
        $pdo = Database::pdo();
        $pdo->prepare('UPDATE usuarios SET status = "bloqueado", atualizado_em = NOW() WHERE id = ?')->execute([$id]);
        JsonResponse::ok(['blocked' => true]);
    }

    public static function unblock(array $body): void
    {
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) { JsonResponse::error('id obrigatório', 422); return; }
        $pdo = Database::pdo();
        $pdo->prepare('UPDATE usuarios SET status = "ativo", atualizado_em = NOW() WHERE id = ?')->execute([$id]);
        JsonResponse::ok(['unblocked' => true]);
    }

    public static function resetPassword(array $body): void
    {
        $id = (int)($body['id'] ?? 0);
        $nova = (string)($body['senha'] ?? '');
        if ($id <= 0 || $nova === '') { JsonResponse::error('id e senha são obrigatórios', 422); return; }
        $hash = password_hash($nova, PASSWORD_BCRYPT);
        $pdo = Database::pdo();
        $pdo->prepare('UPDATE usuarios SET senha_hash = ?, atualizado_em = NOW() WHERE id = ?')->execute([$hash, $id]);
        JsonResponse::ok(['reset' => true]);
    }
}


