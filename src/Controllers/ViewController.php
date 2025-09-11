<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Http\JsonResponse;
use PDO;

final class ViewController
{
    /**
     * Lê manifesto de álbum, se existir, no formato album.json
     * { "tracks": [{"file":"01 - Intro.mp3","title":"Intro","duration":180}, ...] }
     */
    private static function readAlbumManifest(string $dir): array
    {
        $manifestPath = rtrim($dir, "/\\") . '/album.json';
        if (!is_file($manifestPath)) { return []; }
        try {
            $json = @file_get_contents($manifestPath);
            if ($json === false) { return []; }
            /** @var array<string,mixed> $data */
            $data = json_decode($json, true) ?: [];
            $tracks = isset($data['tracks']) && is_array($data['tracks']) ? $data['tracks'] : [];
            $byFile = [];
            foreach ($tracks as $t) {
                if (!is_array($t)) { continue; }
                $file = (string)($t['file'] ?? '');
                if ($file === '') { continue; }
                $byFile[$file] = [
                    'title' => isset($t['title']) ? (string)$t['title'] : '',
                    'duration' => isset($t['duration']) && is_numeric($t['duration']) ? (int)$t['duration'] : null,
                ];
            }
            return $byFile;
        } catch (\Throwable $_) { return []; }
    }

    /**
     * Cache simples de duração por arquivo em .album_cache.json
     */
    private static function readAlbumCache(string $dir): array
    {
        $cachePath = rtrim($dir, "/\\") . '/.album_cache.json';
        if (!is_file($cachePath)) { return []; }
        try {
            $json = @file_get_contents($cachePath);
            if ($json === false) { return []; }
            $data = json_decode($json, true);
            return is_array($data) ? $data : [];
        } catch (\Throwable $_) { return []; }
    }

    private static function writeAlbumCache(string $dir, array $cache): void
    {
        $cachePath = rtrim($dir, "/\\") . '/.album_cache.json';
        try {
            @file_put_contents($cachePath, json_encode($cache, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } catch (\Throwable $_) {
            // ignore
        }
    }

    /**
     * Tenta obter duração (segundos) via ID3v2 TLEN (ms). Retorna null se não encontrado.
     */
    private static function readMp3DurationSeconds(string $path): ?int
    {
        $fh = @fopen($path, 'rb');
        if (!$fh) { return null; }
        try {
            $header = fread($fh, 10);
            if ($header === false || strlen($header) < 10) { return null; }
            if (substr($header, 0, 3) !== 'ID3') { return null; }
            $ver = ord($header[3]); // 3=v2.3, 4=v2.4
            // synchsafe size (bytes 6-9)
            $size = (ord($header[6]) & 0x7F) << 21 | (ord($header[7]) & 0x7F) << 14 | (ord($header[8]) & 0x7F) << 7 | (ord($header[9]) & 0x7F);
            $toRead = $size;
            while ($toRead > 10) {
                $frameHeader = fread($fh, 10);
                if ($frameHeader === false || strlen($frameHeader) < 10) { break; }
                $id = substr($frameHeader, 0, 4);
                if (!preg_match('/^[A-Z0-9]{4}$/', $id)) { break; }
                if ($ver >= 4) {
                    // v2.4: size é synchsafe
                    $fs = (ord($frameHeader[4]) & 0x7F) << 21 | (ord($frameHeader[5]) & 0x7F) << 14 | (ord($frameHeader[6]) & 0x7F) << 7 | (ord($frameHeader[7]) & 0x7F);
                } else {
                    // v2.3: size normal 32-bit big-endian
                    $fs = (ord($frameHeader[4]) << 24) | (ord($frameHeader[5]) << 16) | (ord($frameHeader[6]) << 8) | ord($frameHeader[7]);
                }
                $flags = substr($frameHeader, 8, 2); // ignorado
                if ($fs <= 0) { break; }
                $data = ($fs > 0) ? fread($fh, $fs) : '';
                if ($data === false) { break; }
                if ($id === 'TLEN') {
                    // TLEN em ms, pode vir com encoding/txt frame header. Vamos extrair dígitos.
                    $digits = preg_replace('/[^0-9]/', '', $data);
                    if ($digits !== null && $digits !== '') {
                        $ms = (int)$digits;
                        if ($ms > 0) { return (int)round($ms / 1000); }
                    }
                }
                $toRead -= (10 + $fs);
            }
            return null;
        } catch (\Throwable $_) {
            return null;
        } finally {
            @fclose($fh);
        }
    }
    /**
     * Renderiza uma página do PDF como imagem (view-only), com marca d'água.
     * Query: produto_id (int), page (1-based), width (px opcional, default 1200)
     */
    public static function pdfPage(array $_body, array $req): void
    {
        $produtoId = (int)($_GET['produto_id'] ?? 0);
        $page = max(1, (int)($_GET['page'] ?? 1));
        $width = max(400, (int)($_GET['width'] ?? 1200));
        if ($produtoId <= 0) { JsonResponse::error('produto_id é obrigatório', 422); return; }

        if (!extension_loaded('imagick')) {
            JsonResponse::error('Servidor sem suporte a Imagick para PDF', 501);
            return;
        }

        $userId = (int)($req['user_id'] ?? 0);
        $pdo = Database::pdo();
        // Verifica se há acesso (independe de D+7 para visualização)
        $stmt = $pdo->prepare('SELECT COALESCE(p.storage_view_pdf, p.storage_path_pdf) AS storage_path_pdf, u.email FROM acessos a JOIN produtos p ON p.id = a.produto_id JOIN usuarios u ON u.id = a.usuario_id WHERE a.usuario_id = ? AND a.produto_id = ? AND a.status = "ativo" AND p.ativo = 1 LIMIT 1');
        $stmt->execute([$userId, $produtoId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || empty($row['storage_path_pdf'])) {
            JsonResponse::error('Sem acesso ou PDF não configurado', 403);
            return;
        }
        $path = (string)$row['storage_path_pdf'];
        if (!is_file($path)) { JsonResponse::error('Arquivo não encontrado', 404); return; }

        // Renderiza página
        try {
            $imagick = new \Imagick();
            $imagick->setResolution(150, 150);
            $imagick->readImage($path . '[' . ($page - 1) . ']');
            $imagick->setImageFormat('jpeg');
            // Redimensiona mantendo proporção
            $imagick->resizeImage($width, 0, \Imagick::FILTER_LANCZOS, 1);

            // Marca d'água discreta com e-mail
            $draw = new \ImagickDraw();
            $draw->setFillColor(new \ImagickPixel('rgba(255,255,255,0.35)'));
            $draw->setStrokeColor(new \ImagickPixel('rgba(0,0,0,0.35)'));
            $draw->setFontSize(18);
            $text = 'Eros Vitta — Uso exclusivo de ' . (string)$row['email'];
            // Diagonal
            $imagick->annotateImage($draw, 20, 40, -15, $text);

            header('Content-Type: image/jpeg');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            echo $imagick->getImageBlob();
        } catch (\Throwable $e) {
            JsonResponse::error('Falha ao renderizar página', 500, ['message' => $e->getMessage()]);
        }
    }

    /**
     * Stream de áudio inline (view-only). Não impede download por usuários avançados,
     * mas envia cabeçalhos para desestimular salvamento.
     * Query: produto_id (int)
     */
    public static function audio(array $_body, array $req): void
    {
        $produtoId = (int)($_GET['produto_id'] ?? 0);
        if ($produtoId <= 0) { JsonResponse::error('produto_id é obrigatório', 422); return; }
        $userId = (int)($req['user_id'] ?? 0);
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT COALESCE(p.storage_view_audio, p.storage_path_audio) AS storage_path_audio FROM acessos a JOIN produtos p ON p.id = a.produto_id WHERE a.usuario_id = ? AND a.produto_id = ? AND a.status = "ativo" AND p.ativo = 1 LIMIT 1');
        $stmt->execute([$userId, $produtoId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || empty($row['storage_path_audio'])) {
            JsonResponse::error('Sem acesso ou Áudio não configurado', 403);
            return;
        }
        $path = (string)$row['storage_path_audio'];
        if (!is_file($path)) { JsonResponse::error('Arquivo não encontrado', 404); return; }

        header('Content-Type: audio/mpeg');
        header('Content-Disposition: inline; filename="' . basename($path) . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('X-Content-Type-Options: nosniff');
        header('Accept-Ranges: none');

        readfile($path);
    }

    /**
     * Retorna o PDF completo (view-only) para uso no PDF.js do front-end.
     * Query: produto_id (int)
     */
    public static function pdfFile(array $_body, array $req): void
    {
        $produtoId = (int)($_GET['produto_id'] ?? 0);
        if ($produtoId <= 0) { JsonResponse::error('produto_id é obrigatório', 422); return; }
        $userId = (int)($req['user_id'] ?? 0);
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT COALESCE(p.storage_view_pdf, p.storage_path_pdf) AS storage_path_pdf FROM acessos a JOIN produtos p ON p.id = a.produto_id WHERE a.usuario_id = ? AND a.produto_id = ? AND a.status = "ativo" AND p.ativo = 1 LIMIT 1');
        $stmt->execute([$userId, $produtoId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || empty($row['storage_path_pdf'])) { JsonResponse::error('Sem acesso ou PDF não configurado', 403); return; }
        $path = (string)$row['storage_path_pdf'];
        if (!is_file($path)) { JsonResponse::error('Arquivo não encontrado', 404); return; }
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($path) . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        readfile($path);
    }

    /**
     * Playlist de faixas do produto (view-only). Requer acesso ativo.
     * Query: produto_id (int)
     */
    public static function playlist(array $_body, array $req): void
    {
        $produtoId = (int)($_GET['produto_id'] ?? 0);
        if ($produtoId <= 0) { JsonResponse::error('produto_id é obrigatório', 422); return; }
        $userId = (int)($req['user_id'] ?? 0);
        $pdo = Database::pdo();
        // Verifica acesso e produto ativo
        $stmt = $pdo->prepare('SELECT 1 FROM acessos a JOIN produtos p ON p.id = a.produto_id WHERE a.usuario_id = ? AND a.produto_id = ? AND a.status = "ativo" AND p.ativo = 1 LIMIT 1');
        $stmt->execute([$userId, $produtoId]);
        if (!$stmt->fetch()) { JsonResponse::error('Sem acesso ao produto', 403); return; }
        // Tenta playlist por pasta (diretório configurado no produto)
        $dirStmt = $pdo->prepare('SELECT storage_view_audio_dir FROM produtos WHERE id = ? LIMIT 1');
        $dirStmt->execute([$produtoId]);
        $dirRow = $dirStmt->fetch(PDO::FETCH_ASSOC);
        $items = [];
        $madeFromDir = false;
        if ($dirRow && !empty($dirRow['storage_view_audio_dir'])) {
            $dir = (string)$dirRow['storage_view_audio_dir'];
            if (is_dir($dir)) {
                $files = glob(rtrim($dir, '/\\') . '/*.mp3');
                if (is_array($files) && count($files) > 0) {
                    natsort($files);
                    $files = array_values($files);
                    // Manifesto e cache
                    $manifest = self::readAlbumManifest($dir); // por nome de arquivo
                    $cache = self::readAlbumCache($dir);       // por caminho completo
                    $index = 1;
                    $changedCache = false;
                    foreach ($files as $filePath) {
                        $fileName = basename($filePath);
                        $base = basename($filePath, '.mp3');
                        $title = isset($manifest[$fileName]['title']) && $manifest[$fileName]['title'] !== ''
                            ? (string)$manifest[$fileName]['title']
                            : trim(str_replace(['_', '-'], ' ', $base));
                        $duration = null;
                        if (isset($manifest[$fileName]['duration']) && is_numeric($manifest[$fileName]['duration'])) {
                            $duration = (int)$manifest[$fileName]['duration'];
                        } elseif (isset($cache[$filePath]) && is_numeric($cache[$filePath])) {
                            $duration = (int)$cache[$filePath];
                        } else {
                            $duration = self::readMp3DurationSeconds($filePath);
                            if ($duration !== null) { $cache[$filePath] = $duration; $changedCache = true; }
                        }
                        $items[] = [
                            'id' => $index, // índice 1-based para modo pasta
                            'titulo' => $title !== '' ? $title : ('Faixa ' . $index),
                            'ordem' => $index,
                            'duracao_segundos' => $duration,
                        ];
                        $index++;
                    }
                    if ($changedCache) { self::writeAlbumCache($dir, $cache); }
                    $madeFromDir = true;
                }
            }
        }
        if (!$madeFromDir) {
            JsonResponse::ok(['items' => []]);
            return;
        }
        JsonResponse::ok(['items' => $items]);
    }

    /**
     * Stream de uma faixa específica (view-only)
     * Query: produto_id (int), track_id (int)
     */
    public static function audioTrack(array $_body, array $req): void
    {
        $produtoId = (int)($_GET['produto_id'] ?? 0);
        $trackId = (int)($_GET['track_id'] ?? 0); // pode ser ID da faixa (DB) ou índice 1-based (pasta)
        if ($produtoId <= 0 || $trackId <= 0) { JsonResponse::error('produto_id e track_id são obrigatórios', 422); return; }
        $userId = (int)($req['user_id'] ?? 0);
        $pdo = Database::pdo();
        // Verifica acesso e produto ativo
        $stmt = $pdo->prepare('SELECT 1 FROM acessos a JOIN produtos p ON p.id = a.produto_id WHERE a.usuario_id = ? AND a.produto_id = ? AND a.status = "ativo" AND p.ativo = 1 LIMIT 1');
        $stmt->execute([$userId, $produtoId]);
        if (!$stmt->fetch()) { JsonResponse::error('Sem acesso ao produto', 403); return; }
        // Modo pasta: `trackId` é o índice 1-based da faixa dentro do diretório
        $path = '';
        {
            $dirStmt = $pdo->prepare('SELECT storage_view_audio_dir FROM produtos WHERE id = ? LIMIT 1');
            $dirStmt->execute([$produtoId]);
            $dirRow = $dirStmt->fetch(PDO::FETCH_ASSOC);
            if ($dirRow && !empty($dirRow['storage_view_audio_dir'])) {
                $dir = (string)$dirRow['storage_view_audio_dir'];
                if (is_dir($dir)) {
                    $files = glob(rtrim($dir, '/\\') . '/*.mp3');
                    if (is_array($files) && count($files) > 0) {
                        natsort($files);
                        $files = array_values($files); // reindexa após sort
                        $index = $trackId - 1; // 1-based → 0-based
                        if ($index >= 0 && $index < count($files)) {
                            $path = $files[$index];
                        }
                    }
                }
            }
        }
        if ($path === '' || !is_file($path)) { JsonResponse::error('Arquivo não encontrado', 404); return; }
        header('Content-Type: audio/mpeg');
        header('Content-Disposition: inline; filename="' . basename($path) . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Accept-Ranges: none');
        readfile($path);
    }
}


