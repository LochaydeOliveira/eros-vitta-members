<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Http\JsonResponse;
use PDO;
use PDOException;

final class AdminAccessController
{
    /**
     * Atribui acesso manual a um usuário para um produto.
     * Body esperado: { usuario_id: int, produto_id: int, data_liberacao?: string(YYYY-MM-DD HH:MM:SS) }
     */
    public static function assign(array $body): void
    {
        $usuarioId = (int)($body['usuario_id'] ?? 0);
        $produtoId = (int)($body['produto_id'] ?? 0);
        $dataLiberacao = trim((string)($body['data_liberacao'] ?? ''));
        if ($usuarioId <= 0 || $produtoId <= 0) {
            JsonResponse::error('usuario_id e produto_id são obrigatórios', 422);
            return;
        }
        // Se não informado, libera imediatamente
        if ($dataLiberacao === '') {
            $dataLiberacao = date('Y-m-d H:i:s');
        }
        $pdo = Database::pdo();
        try {
            $sql = 'INSERT INTO acessos (usuario_id, produto_id, origem, status, data_liberacao, criado_em, atualizado_em)
                    VALUES (?,?,?,?,?, NOW(), NOW())
                    ON DUPLICATE KEY UPDATE 
                        origem = VALUES(origem),
                        status = "ativo",
                        data_liberacao = VALUES(data_liberacao),
                        data_bloqueio = NULL,
                        motivo_bloqueio = NULL,
                        atualizado_em = NOW()';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$usuarioId, $produtoId, 'manual', 'ativo', $dataLiberacao]);
            JsonResponse::ok(['assigned' => true]);
        } catch (PDOException $e) {
            JsonResponse::error('Falha ao atribuir acesso', 400, [
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Lista acessos de um usuário (admin)
     * Query: usuario_id (int)
     */
    public static function listByUser(array $_body, array $req): void
    {
        $usuarioId = (int)($req['usuario_id'] ?? 0);
        if ($usuarioId <= 0) { JsonResponse::error('usuario_id é obrigatório', 422); return; }
        $pdo = Database::pdo();
        $sql = "
            SELECT a.id AS acesso_id, a.produto_id, a.status, a.data_liberacao, a.data_bloqueio, a.motivo_bloqueio,
                   p.titulo, p.tipo
            FROM (
              SELECT a1.*
              FROM acessos a1
              JOIN (
                SELECT produto_id, MAX(id) AS max_id
                FROM acessos
                WHERE usuario_id = ?
                GROUP BY produto_id
              ) mx ON mx.max_id = a1.id
            ) a
            JOIN produtos p ON p.id = a.produto_id
            ORDER BY p.titulo ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$usuarioId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        JsonResponse::ok(['items' => $items]);
    }

    /**
     * Bloqueia o acesso de um usuário a um produto
     * Body: { usuario_id:int, produto_id:int, motivo?:string }
     */
    public static function block(array $body): void
    {
        $usuarioId = (int)($body['usuario_id'] ?? 0);
        $produtoId = (int)($body['produto_id'] ?? 0);
        $motivo = trim((string)($body['motivo'] ?? 'manual'));
        if ($usuarioId <= 0 || $produtoId <= 0) { JsonResponse::error('usuario_id e produto_id são obrigatórios', 422); return; }
        $pdo = Database::pdo();
        $sql = 'UPDATE acessos SET status = "bloqueado", data_bloqueio = NOW(), motivo_bloqueio = ?, atualizado_em = NOW() WHERE usuario_id = ? AND produto_id = ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$motivo !== '' ? $motivo : 'manual', $usuarioId, $produtoId]);
        JsonResponse::ok(['blocked' => true]);
    }

    /**
     * Atualiza status do acesso: ativo|bloqueado
     * Body: { usuario_id:int, produto_id:int, status:'ativo'|'bloqueado', motivo?:string }
     */
    public static function updateStatus(array $body): void
    {
        $usuarioId = (int)($body['usuario_id'] ?? 0);
        $produtoId = (int)($body['produto_id'] ?? 0);
        $status = (string)($body['status'] ?? '');
        $motivo = trim((string)($body['motivo'] ?? 'manual'));
        if ($usuarioId <= 0 || $produtoId <= 0 || !in_array($status, ['ativo','bloqueado'], true)) {
            JsonResponse::error('usuario_id, produto_id e status válidos são obrigatórios', 422);
            return;
        }
        $pdo = Database::pdo();
        // Garante que existe registro de acesso; se não houver, cria um e define o status
        $check = $pdo->prepare('SELECT id FROM acessos WHERE usuario_id = ? AND produto_id = ? ORDER BY id DESC LIMIT 1');
        $check->execute([$usuarioId, $produtoId]);
        $row = $check->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            if ($status === 'ativo') {
                $sql = 'UPDATE acessos SET status = "ativo", data_bloqueio = NULL, motivo_bloqueio = NULL, atualizado_em = NOW() WHERE usuario_id = ? AND produto_id = ?';
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$usuarioId, $produtoId]);
            } else {
                $sql = 'UPDATE acessos SET status = "bloqueado", data_bloqueio = NOW(), motivo_bloqueio = ?, atualizado_em = NOW() WHERE usuario_id = ? AND produto_id = ?';
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$motivo !== '' ? $motivo : 'manual', $usuarioId, $produtoId]);
            }
        } else {
            // cria novo registro quando não existir acesso anterior
            $sql = 'INSERT INTO acessos (usuario_id, produto_id, origem, status, data_liberacao, criado_em, atualizado_em) VALUES (?,?,?,?,NOW(), NOW(), NOW())';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$usuarioId, $produtoId, 'manual', $status === 'ativo' ? 'ativo' : 'bloqueado']);
        }
        // normaliza duplicatas antigas: mantém apenas o último registro por usuario/produto
        $pdo->prepare('DELETE a FROM acessos a JOIN acessos b ON a.usuario_id=b.usuario_id AND a.produto_id=b.produto_id AND a.id < b.id')->execute();
        JsonResponse::ok(['updated' => true]);
    }
}


