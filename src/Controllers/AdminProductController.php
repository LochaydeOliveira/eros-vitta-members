<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Http\JsonResponse;
use PDO;

final class AdminProductController
{
    public static function list(): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->query('SELECT id, titulo, tipo, slug, descricao, capa_url, ativo, hotmart_product_id, storage_path_pdf, storage_path_audio FROM produtos ORDER BY id DESC');
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        JsonResponse::ok(['items' => $items]);
    }

    public static function create(array $body): void
    {
        $titulo = trim((string)($body['titulo'] ?? ''));
        $tipo = (string)($body['tipo'] ?? 'ebook'); // ebook|audio
        $slug = trim((string)($body['slug'] ?? ''));
        $descricao = (string)($body['descricao'] ?? '');
        $capaUrl = (string)($body['capa_url'] ?? '');
        $ativo = (int)($body['ativo'] ?? 1);
        $hotmartId = (string)($body['hotmart_product_id'] ?? '');
        $pdfPath = (string)($body['storage_path_pdf'] ?? '');
        $audioPath = (string)($body['storage_path_audio'] ?? '');
        if ($titulo === '' || $slug === '' || !in_array($tipo, ['ebook','audio'], true)) {
            JsonResponse::error('Campos obrigatórios: titulo, slug, tipo[ebook|audio]', 422);
            return;
        }
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('INSERT INTO produtos (titulo, tipo, slug, descricao, capa_url, ativo, hotmart_product_id, storage_path_pdf, storage_path_audio, criado_em, atualizado_em) VALUES (?,?,?,?,?,?,?,?,?, NOW(), NOW())');
        $stmt->execute([$titulo, $tipo, $slug, $descricao, $capaUrl, $ativo, $hotmartId ?: null, $pdfPath ?: null, $audioPath ?: null]);
        JsonResponse::ok(['id' => (int)$pdo->lastInsertId()], 201);
    }

    public static function update(array $body): void
    {
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) {
            JsonResponse::error('id obrigatório', 422);
            return;
        }
        $fields = ['titulo','tipo','slug','descricao','capa_url','ativo','hotmart_product_id','storage_path_pdf','storage_path_audio'];
        $set = [];
        $values = [];
        foreach ($fields as $f) {
            if (array_key_exists($f, $body)) {
                $set[] = $f . ' = ?';
                $values[] = $body[$f] === '' ? null : $body[$f];
            }
        }
        if (!$set) {
            JsonResponse::error('Nada para atualizar', 400);
            return;
        }
        $values[] = $id;
        $pdo = Database::pdo();
        $sql = 'UPDATE produtos SET ' . implode(', ', $set) . ', atualizado_em = NOW() WHERE id = ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        JsonResponse::ok(['updated' => true]);
    }

    public static function deactivate(array $body): void
    {
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) {
            JsonResponse::error('id obrigatório', 422);
            return;
        }
        $pdo = Database::pdo();
        $pdo->prepare('UPDATE produtos SET ativo = 0, atualizado_em = NOW() WHERE id = ?')->execute([$id]);
        JsonResponse::ok(['deactivated' => true]);
    }
}


