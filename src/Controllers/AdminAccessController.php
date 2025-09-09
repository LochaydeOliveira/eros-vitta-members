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
}


