<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Http\JsonResponse;
use PDO;

final class AdminViewController
{
    private static function resolveFilePath(string $input): string
    {
        $input = trim($input);
        if ($input === '') { return ''; }
        if (is_file($input)) { return $input; }
        // Se for URL, pega apenas o path
        if (stripos($input, 'http://') === 0 || stripos($input, 'https://') === 0) {
            $urlPath = parse_url($input, PHP_URL_PATH) ?: '';
        } else {
            $urlPath = $input;
        }
        // Se já for um caminho absoluto tipo /home1/... tenta diretamente
        if ($urlPath !== '' && $urlPath[0] === '/' && is_file($urlPath)) { return $urlPath; }
        // Mapeia /storage/... para filesystem baseado no DOCUMENT_ROOT
        if ($urlPath !== '' && $urlPath[0] === '/') {
            $docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
            if ($docRoot !== '') {
                $base = rtrim(dirname($docRoot), '/');
                $candidate = $base . $urlPath;
                if (is_file($candidate)) { return $candidate; }
            }
        }
        return '';
    }

    private static function resolveDirPath(string $input): string
    {
        $input = trim($input);
        if ($input === '') { return ''; }
        if (is_dir($input)) { return $input; }
        if (stripos($input, 'http://') === 0 || stripos($input, 'https://') === 0) {
            $urlPath = parse_url($input, PHP_URL_PATH) ?: '';
        } else {
            $urlPath = $input;
        }
        if ($urlPath !== '' && $urlPath[0] === '/' && is_dir($urlPath)) { return $urlPath; }
        if ($urlPath !== '' && $urlPath[0] === '/') {
            $docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
            if ($docRoot !== '') {
                $base = rtrim(dirname($docRoot), '/');
                $candidate = $base . $urlPath;
                if (is_dir($candidate)) { return $candidate; }
            }
        }
        return '';
    }
    /**
     * Preview PDF (somente admin). Query: produto_id (int)
     */
    public static function pdfFile(array $_body, array $_request): void
    {
        $produtoId = (int)($_GET['produto_id'] ?? 0);
        if ($produtoId <= 0) { JsonResponse::error('produto_id é obrigatório', 422); return; }
        // Bloqueia preview se produto estiver inativo
        $pdo = Database::pdo();
        $activeStmt = $pdo->prepare('SELECT ativo FROM produtos WHERE id = ? LIMIT 1');
        $activeStmt->execute([$produtoId]);
        $activeRow = $activeStmt->fetch(PDO::FETCH_ASSOC);
        if (!$activeRow || (int)$activeRow['ativo'] !== 1) { JsonResponse::error('Produto inativo', 403); return; }
        $stmt = $pdo->prepare('SELECT storage_view_pdf, storage_path_pdf FROM produtos WHERE id = ? LIMIT 1');
        $stmt->execute([$produtoId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) { JsonResponse::error('PDF não configurado', 404); return; }
        $path = '';
        if (!empty($row['storage_path_pdf'])) { $path = self::resolveFilePath((string)$row['storage_path_pdf']); }
        if ($path === '' && !empty($row['storage_view_pdf'])) { $path = self::resolveFilePath((string)$row['storage_view_pdf']); }
        if ($path === '') { JsonResponse::error('Arquivo não encontrado', 404); return; }
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($path) . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        readfile($path);
    }

    /**
     * Lista playlist de áudio (somente admin). Query: produto_id (int)
     */
    public static function playlist(array $_body, array $_request): void
    {
        $produtoId = (int)($_GET['produto_id'] ?? 0);
        if ($produtoId <= 0) { JsonResponse::error('produto_id é obrigatório', 422); return; }
        $pdo = Database::pdo();
        $activeStmt = $pdo->prepare('SELECT ativo FROM produtos WHERE id = ? LIMIT 1');
        $activeStmt->execute([$produtoId]);
        $activeRow = $activeStmt->fetch(PDO::FETCH_ASSOC);
        if (!$activeRow || (int)$activeRow['ativo'] !== 1) { JsonResponse::ok(['items' => []]); return; }
        $stmt = $pdo->prepare('SELECT storage_view_audio_dir FROM produtos WHERE id = ? LIMIT 1');
        $stmt->execute([$produtoId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || empty($row['storage_view_audio_dir'])) { JsonResponse::ok(['items' => []]); return; }
        $dir = self::resolveDirPath((string)$row['storage_view_audio_dir']);
        $items = [];
        if (is_dir($dir)) {
            $files = glob(rtrim($dir, '/\\') . '/*.mp3');
            if (is_array($files) && count($files) > 0) {
                natsort($files);
                $files = array_values($files);
                $i = 1;
                foreach ($files as $file) {
                    $items[] = [
                        'id' => $i,
                        'titulo' => pathinfo($file, PATHINFO_FILENAME),
                        'ordem' => $i,
                    ];
                    $i++;
                }
            }
        }
        JsonResponse::ok(['items' => $items]);
    }

    /**
     * Stream de uma faixa (somente admin). Query: produto_id (int), track_id (int)
     */
    public static function audioTrack(array $_body, array $_request): void
    {
        $produtoId = (int)($_GET['produto_id'] ?? 0);
        $trackId = (int)($_GET['track_id'] ?? 0);
        if ($produtoId <= 0 || $trackId <= 0) { JsonResponse::error('produto_id e track_id são obrigatórios', 422); return; }
        $pdo = Database::pdo();
        $activeStmt = $pdo->prepare('SELECT ativo FROM produtos WHERE id = ? LIMIT 1');
        $activeStmt->execute([$produtoId]);
        $activeRow = $activeStmt->fetch(PDO::FETCH_ASSOC);
        if (!$activeRow || (int)$activeRow['ativo'] !== 1) { JsonResponse::error('Produto inativo', 403); return; }
        $stmt = $pdo->prepare('SELECT storage_view_audio_dir FROM produtos WHERE id = ? LIMIT 1');
        $stmt->execute([$produtoId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || empty($row['storage_view_audio_dir'])) { JsonResponse::error('Áudio não configurado', 404); return; }
        $dir = self::resolveDirPath((string)$row['storage_view_audio_dir']);
        $path = '';
        if (is_dir($dir)) {
            $files = glob(rtrim($dir, '/\\') . '/*.mp3');
            if (is_array($files) && count($files) > 0) {
                natsort($files);
                $files = array_values($files);
                $idx = $trackId - 1; // 1-based → 0-based
                if ($idx >= 0 && $idx < count($files)) { $path = $files[$idx]; }
            }
        }
        if ($path === '' || !is_file($path)) { JsonResponse::error('Arquivo não encontrado', 404); return; }
        header('Content-Type: audio/mpeg');
        header('Content-Disposition: inline; filename="' . basename($path) . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        readfile($path);
    }

    /**
     * Stream de um único arquivo de áudio (somente admin). Query: produto_id (int)
     */
    public static function audioFile(array $_body, array $_request): void
    {
        $produtoId = (int)($_GET['produto_id'] ?? 0);
        if ($produtoId <= 0) { JsonResponse::error('produto_id é obrigatório', 422); return; }
        $pdo = Database::pdo();
        $activeStmt = $pdo->prepare('SELECT ativo FROM produtos WHERE id = ? LIMIT 1');
        $activeStmt->execute([$produtoId]);
        $activeRow = $activeStmt->fetch(PDO::FETCH_ASSOC);
        if (!$activeRow || (int)$activeRow['ativo'] !== 1) { JsonResponse::error('Produto inativo', 403); return; }
        $stmt = $pdo->prepare('SELECT storage_path_audio FROM produtos WHERE id = ? LIMIT 1');
        $stmt->execute([$produtoId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || empty($row['storage_path_audio'])) { JsonResponse::error('Áudio não configurado', 404); return; }
        $path = self::resolveFilePath((string)$row['storage_path_audio']);
        if ($path === '') { JsonResponse::error('Arquivo não encontrado', 404); return; }
        header('Content-Type: audio/mpeg');
        header('Content-Disposition: inline; filename="' . basename($path) . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        readfile($path);
    }
}


