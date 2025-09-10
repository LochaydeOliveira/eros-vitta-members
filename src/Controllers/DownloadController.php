<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Http\JsonResponse;
use PDO;

final class DownloadController
{
    public static function createToken(array $body, array $req): void
    {
        $userId = (int)($req['user_id'] ?? 0);
        $produtoId = (int)($body['produto_id'] ?? 0);
        if ($produtoId <= 0) {
            JsonResponse::error('produto_id obrigatório', 422);
            return;
        }
        $pdo = Database::pdo();
        // Verifica acesso e liberação
        $stmt = $pdo->prepare('SELECT data_liberacao FROM acessos WHERE usuario_id = ? AND produto_id = ? AND status = "ativo" LIMIT 1');
        $stmt->execute([$userId, $produtoId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            JsonResponse::error('Acesso não encontrado', 403);
            return;
        }
        if (!empty($row['data_liberacao']) && (new \DateTime($row['data_liberacao'])) > new \DateTime()) {
            JsonResponse::error('Download ainda não liberado', 403);
            return;
        }
        $token = bin2hex(random_bytes(32));
        $pdo->prepare('INSERT INTO download_tokens (usuario_id, produto_id, token, expira_em, criado_em) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE), NOW())')
            ->execute([$userId, $produtoId, $token]);
        JsonResponse::ok(['token' => $token, 'expira_em' => date('c', time() + 900)]);
    }

    public static function streamByToken(): void
    {
        $token = (string)($_GET['token'] ?? '');
        if ($token === '') {
            JsonResponse::error('Token é obrigatório', 422);
            return;
        }
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT dt.usuario_id, dt.produto_id, dt.usado_em, dt.expira_em, p.tipo, p.storage_path_pdf, p.storage_path_audio FROM download_tokens dt JOIN produtos p ON p.id = dt.produto_id WHERE dt.token = ? LIMIT 1');
        $stmt->execute([$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            JsonResponse::error('Token inválido', 404);
            return;
        }
        if (!empty($row['usado_em']) || (new \DateTime($row['expira_em'])) < new \DateTime()) {
            JsonResponse::error('Token expirado ou já utilizado', 410);
            return;
        }
        $path = null;
        $mime = 'application/octet-stream';
        if ($row['tipo'] === 'ebook') {
            $path = (string)$row['storage_path_pdf'];
            $mime = 'application/pdf';
        } else {
            $path = (string)$row['storage_path_audio'];
            $mime = 'audio/mpeg';
        }
        if (!$path || !is_file($path)) {
            JsonResponse::error('Arquivo não encontrado', 404);
            return;
        }
        // Marca como usado (opcional, pode permitir múltiplos até expirar)
        $pdo->prepare('UPDATE download_tokens SET usado_em = NOW() WHERE token = ?')->execute([$token]);
        // Stream simples
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string)filesize($path));
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        readfile($path);
    }
}
