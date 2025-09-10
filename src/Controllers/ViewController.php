<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Http\JsonResponse;
use PDO;

final class ViewController
{
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
        $stmt = $pdo->prepare('SELECT p.storage_path_pdf, u.email FROM acessos a JOIN produtos p ON p.id = a.produto_id JOIN usuarios u ON u.id = a.usuario_id WHERE a.usuario_id = ? AND a.produto_id = ? AND a.status = "ativo" LIMIT 1');
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
        $stmt = $pdo->prepare('SELECT p.storage_path_audio FROM acessos a JOIN produtos p ON p.id = a.produto_id WHERE a.usuario_id = ? AND a.produto_id = ? AND a.status = "ativo" LIMIT 1');
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
}


