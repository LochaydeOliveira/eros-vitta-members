<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Http\JsonResponse;
use PDO;

final class AdminProductController
{
    /**
     * @param array<string,mixed> $_body
     * @param array<string,mixed> $_request
     */
    public static function list(array $_body = [], array $_request = []): void
    {
        $pdo = Database::pdo();
        // Filtros opcionais via query string
        $where = [];
        $params = [];
        $tipo = isset($_GET['tipo']) ? (string)$_GET['tipo'] : '';
        if ($tipo !== '' && in_array($tipo, ['ebook','audio'], true)) { $where[] = 'tipo = ?'; $params[] = $tipo; }
        if (isset($_GET['ativo'])) { $where[] = 'ativo = ?'; $params[] = (int)$_GET['ativo'] === 1 ? 1 : 0; }
        $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        if ($q !== '') { $where[] = '(titulo LIKE ? OR slug LIKE ?)'; $like = '%' . $q . '%'; $params[] = $like; $params[] = $like; }
        $sql = 'SELECT id, titulo, tipo, slug, descricao, capa_url, ativo, hotmart_product_id, checkout_url, storage_path_pdf, storage_view_pdf, storage_path_audio, storage_view_audio_dir FROM produtos';
        if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
        // Ordenação
        $allowedOrder = ['id','titulo','tipo','ativo','criado_em','atualizado_em'];
        $order = isset($_GET['order']) ? (string)$_GET['order'] : 'id';
        if (!in_array($order, $allowedOrder, true)) { $order = 'id'; }
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

    /**
     * @param array<string,mixed> $body
     * @param array<string,mixed> $_request
     */
    public static function create(array $body, array $_request = []): void
    {
        $titulo = trim((string)($body['titulo'] ?? ''));
        $tipo = (string)($body['tipo'] ?? 'ebook'); // ebook|audio
        $slug = trim((string)($body['slug'] ?? ''));
        $descricao = (string)($body['descricao'] ?? '');
        $capaUrl = (string)($body['capa_url'] ?? '');
        $ativo = (int)($body['ativo'] ?? 1);
        $hotmartId = (string)($body['hotmart_product_id'] ?? '');
        $pdfPath = (string)($body['storage_path_pdf'] ?? '');
        $pdfView = (string)($body['storage_view_pdf'] ?? '');
        $audioPath = (string)($body['storage_path_audio'] ?? '');
        $audioDir = (string)($body['storage_view_audio_dir'] ?? '');
        if ($titulo === '' || $slug === '' || !in_array($tipo, ['ebook','audio'], true)) {
            JsonResponse::error('Campos obrigatórios: titulo, slug, tipo[ebook|audio]', 422);
            return;
        }
        $pdo = Database::pdo();
        $checkoutUrl = (string)($body['checkout_url'] ?? '');
        $stmt = $pdo->prepare('INSERT INTO produtos (titulo, tipo, slug, descricao, capa_url, ativo, hotmart_product_id, checkout_url, storage_path_pdf, storage_view_pdf, storage_path_audio, storage_view_audio_dir, criado_em, atualizado_em) VALUES (?,?,?,?,?,?,?,?,?,?,?, ?, NOW(), NOW())');
        $stmt->execute([$titulo, $tipo, $slug, $descricao, $capaUrl, $ativo, $hotmartId ?: null, $checkoutUrl ?: null, $pdfPath ?: null, $pdfView ?: null, $audioPath ?: null, $audioDir ?: null]);
        JsonResponse::ok(['id' => (int)$pdo->lastInsertId()], 201);
    }

    /**
     * @param array<string,mixed> $body
     * @param array<string,mixed> $_request
     */
    public static function update(array $body, array $_request = []): void
    {
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) {
            JsonResponse::error('id obrigatório', 422);
            return;
        }
        $fields = ['titulo','tipo','slug','descricao','capa_url','ativo','hotmart_product_id','checkout_url','storage_path_pdf','storage_view_pdf','storage_path_audio','storage_view_audio_dir'];
        $set = [];
        $values = [];
        foreach ($fields as $f) {
            if (array_key_exists($f, $body)) {
                $set[] = $f . ' = ?';
                // Preserva valores string, inclusive caminhos absolutos; converte '' para NULL
                $values[] = ($body[$f] === '' ? null : (is_string($body[$f]) ? trim((string)$body[$f]) : $body[$f]));
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

    /**
     * @param array<string,mixed> $body
     * @param array<string,mixed> $_request
     */
    public static function deactivate(array $body, array $_request = []): void
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


