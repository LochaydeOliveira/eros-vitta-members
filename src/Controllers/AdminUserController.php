<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Http\JsonResponse;
use PDO;

final class AdminUserController
{
    /**
     * @param array<string,mixed> $_body
     * @param array<string,mixed> $_request
     */
    public static function list(array $_body = [], array $_request = []): void
    {
        $pdo = Database::pdo();
        // Filtros
        $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        $where = [];
        $params = [];
        if ($q !== '') {
            $where[] = '(email LIKE ? OR nome LIKE ?)';
            $like = '%' . $q . '%';
            $params[] = $like; $params[] = $like;
        }
        $sql = 'SELECT id, nome, email, status, criado_em, atualizado_em, ultimo_login_em FROM usuarios';
        if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
        // Ordenação
        $allowed = ['id','nome','email','status','criado_em','atualizado_em','ultimo_login_em'];
        $order = isset($_GET['order']) ? (string)$_GET['order'] : 'id';
        if (!in_array($order, $allowed, true)) { $order = 'id'; }
        $dir = strtolower((string)($_GET['dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';
        $sql .= ' ORDER BY ' . $order . ' ' . $dir;
        // Paginação
        $limit = (int)($_GET['limit'] ?? 50); if ($limit <= 0 || $limit > 200) { $limit = 50; }
        $offset = (int)($_GET['offset'] ?? 0); if ($offset < 0) { $offset = 0; }
        $sql .= ' LIMIT ' . $limit . ' OFFSET ' . $offset;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        JsonResponse::ok(['items' => $items, 'limit' => $limit, 'offset' => $offset, 'order' => $order, 'dir' => $dir]);
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


