<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Http\JsonResponse;
use PDO;

final class AdminProductController
{
    private static function getUploadDir(): string
    {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/../storage/covers';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        return $uploadDir;
    }

    private static function getMediaUploadDir(): string
    {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/../storage/media';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        return $uploadDir;
    }

    private static function validateImageFile(array $file): array
    {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['error' => 'Erro no upload: ' . $file['error']];
        }
        
        if ($file['size'] > $maxSize) {
            return ['error' => 'Arquivo muito grande. Máximo 5MB.'];
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes, true)) {
            return ['error' => 'Tipo de arquivo inválido. Use JPG, PNG ou WebP.'];
        }
        
        return ['success' => true, 'mime' => $mimeType];
    }

    private static function validateMediaFile(array $file, string $type): array
    {
        $allowedTypes = match($type) {
            'pdf' => ['application/pdf'],
            'audio' => ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg', 'audio/m4a'],
            default => []
        };
        
        $maxSize = match($type) {
            'pdf' => 50 * 1024 * 1024, // 50MB
            'audio' => 100 * 1024 * 1024, // 100MB
            default => 10 * 1024 * 1024 // 10MB
        };
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['error' => 'Erro no upload: ' . $file['error']];
        }
        
        if ($file['size'] > $maxSize) {
            $maxSizeMB = round($maxSize / 1024 / 1024);
            return ['error' => "Arquivo muito grande. Máximo {$maxSizeMB}MB."];
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes, true)) {
            $allowedExts = match($type) {
                'pdf' => 'PDF',
                'audio' => 'MP3, WAV, OGG, M4A',
                default => 'arquivos válidos'
            };
            return ['error' => "Tipo de arquivo inválido. Use {$allowedExts}."];
        }
        
        return ['success' => true, 'mime' => $mimeType];
    }
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
     * Upload de capa para produto
     * @param array<string,mixed> $_body
     * @param array<string,mixed> $_request
     */
    public static function uploadCover(array $_body = [], array $_request = []): void
    {
        if (!isset($_FILES['cover']) || !is_array($_FILES['cover'])) {
            JsonResponse::error('Arquivo de capa não enviado', 400);
            return;
        }

        $produtoId = (int)($_POST['produto_id'] ?? 0);
        if ($produtoId <= 0) {
            JsonResponse::error('produto_id obrigatório', 422);
            return;
        }

        // Validar se produto existe
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id, titulo FROM produtos WHERE id = ? LIMIT 1');
        $stmt->execute([$produtoId]);
        $produto = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$produto) {
            JsonResponse::error('Produto não encontrado', 404);
            return;
        }

        // Validar arquivo
        $validation = self::validateImageFile($_FILES['cover']);
        if (isset($validation['error'])) {
            JsonResponse::error($validation['error'], 400);
            return;
        }

        // Gerar nome único para o arquivo
        $extension = match($validation['mime']) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg'
        };
        $filename = 'produto_' . $produtoId . '_' . time() . '.' . $extension;
        $uploadDir = self::getUploadDir();
        $filepath = $uploadDir . '/' . $filename;

        // Mover arquivo
        if (!move_uploaded_file($_FILES['cover']['tmp_name'], $filepath)) {
            JsonResponse::error('Falha ao salvar arquivo', 500);
            return;
        }

        // Atualizar capa_url no banco
        $webPath = '/storage/covers/' . $filename;
        $stmt = $pdo->prepare('UPDATE produtos SET capa_url = ?, atualizado_em = NOW() WHERE id = ?');
        $stmt->execute([$webPath, $produtoId]);

        JsonResponse::ok([
            'success' => true,
            'capa_url' => $webPath,
            'filename' => $filename
        ]);
    }

    /**
     * Upload de arquivos de mídia (PDF/Audio)
     */
    public static function uploadMedia(array $_body = [], array $_request = []): void
    {
        if (!isset($_FILES['media']) || !is_array($_FILES['media'])) {
            JsonResponse::error('Arquivo de mídia não enviado', 400);
            return;
        }

        $produtoId = (int)($_POST['produto_id'] ?? 0);
        $mediaType = (string)($_POST['media_type'] ?? '');
        
        if ($produtoId <= 0) {
            JsonResponse::error('produto_id obrigatório', 422);
            return;
        }
        
        if (!in_array($mediaType, ['pdf', 'audio'], true)) {
            JsonResponse::error('media_type deve ser "pdf" ou "audio"', 422);
            return;
        }

        // Validar se produto existe
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id, titulo FROM produtos WHERE id = ? LIMIT 1');
        $stmt->execute([$produtoId]);
        $produto = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$produto) {
            JsonResponse::error('Produto não encontrado', 404);
            return;
        }

        // Validar arquivo
        $validation = self::validateMediaFile($_FILES['media'], $mediaType);
        if (isset($validation['error'])) {
            JsonResponse::error($validation['error'], 400);
            return;
        }

        // Gerar nome único para o arquivo
        $extension = match($mediaType) {
            'pdf' => 'pdf',
            'audio' => match($validation['mime']) {
                'audio/mpeg', 'audio/mp3' => 'mp3',
                'audio/wav' => 'wav',
                'audio/ogg' => 'ogg',
                'audio/m4a' => 'm4a',
                default => 'mp3'
            },
            default => 'bin'
        };
        
        $filename = 'produto_' . $produtoId . '_' . $mediaType . '_' . time() . '.' . $extension;
        $uploadDir = self::getMediaUploadDir();
        $filepath = $uploadDir . '/' . $filename;

        // Mover arquivo
        if (!move_uploaded_file($_FILES['media']['tmp_name'], $filepath)) {
            JsonResponse::error('Falha ao salvar arquivo', 500);
            return;
        }

        // Atualizar campo apropriado no banco
        $webPath = '/storage/media/' . $filename;
        $fieldName = match($mediaType) {
            'pdf' => 'storage_view_pdf',
            'audio' => 'storage_path_audio',
            default => 'storage_view_pdf'
        };
        
        $stmt = $pdo->prepare("UPDATE produtos SET {$fieldName} = ?, atualizado_em = NOW() WHERE id = ?");
        $stmt->execute([$webPath, $produtoId]);

        JsonResponse::ok([
            'success' => true,
            'file_path' => $webPath,
            'filename' => $filename,
            'media_type' => $mediaType
        ]);
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


