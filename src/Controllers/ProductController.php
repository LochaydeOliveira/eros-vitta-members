<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Http\JsonResponse;
use PDO;

final class ProductController
{
    public static function list(array $_body, array $req): void
    {
        $userId = (int)($req['user_id'] ?? 0);
        $pdo = Database::pdo();
        $sql = "
            SELECT
              p.id,
              p.titulo,
              p.tipo,
              p.slug,
              p.descricao,
              p.capa_url,
              p.ativo,
              p.hotmart_product_id,
              p.checkout_url,
              a.status AS acesso_status,
              CASE WHEN a.status = 'ativo' THEN 1 ELSE 0 END AS tem_acesso,
              a.data_liberacao,
              CASE 
                WHEN a.data_liberacao IS NOT NULL AND NOW() >= a.data_liberacao THEN 1
                ELSE 0
              END AS download_liberado
            FROM produtos p
            LEFT JOIN acessos a ON a.produto_id = p.id AND a.usuario_id = ?
            WHERE p.ativo = 1
            ORDER BY p.titulo ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        JsonResponse::ok(['items' => $items]);
    }
}
