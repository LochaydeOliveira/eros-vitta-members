<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Http\JsonResponse;
use PDO;

final class AccessController
{
    public static function list(array $_body, array $req): void
    {
        $userId = (int)($req['user_id'] ?? 0);
        $pdo = Database::pdo();
        $sql = "
            SELECT
              a.id as acesso_id,
              a.produto_id,
              a.status,
              a.data_liberacao,
              p.titulo,
              p.tipo,
              p.slug,
              p.capa_url,
              CASE WHEN a.data_liberacao IS NOT NULL AND NOW() >= a.data_liberacao THEN 1 ELSE 0 END AS download_liberado
            FROM acessos a
            JOIN produtos p ON p.id = a.produto_id
            WHERE a.usuario_id = ?
            ORDER BY p.titulo ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        JsonResponse::ok(['items' => $items]);
    }
}
