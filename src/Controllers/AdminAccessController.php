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
            FROM acessos a
            JOIN produtos p ON p.id = a.produto_id
            WHERE a.usuario_id = ?
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
}


