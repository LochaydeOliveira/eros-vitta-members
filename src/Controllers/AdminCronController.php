<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Http\JsonResponse;
use App\Config;
use App\Mail\Mailer;
use PDO;

final class AdminCronController
{
    /**
     * Dispara manualmente o envio D+7 (para testes). Processa até N acessos.
     * Body opcional: { limit?: number }
     */
    public static function runD7(array $body): void
    {
        $limit = max(1, min(50, (int)($body['limit'] ?? 10)));
        $pdo = Database::pdo();

        $sql = 'SELECT 
                    a.id AS acesso_id,
                    a.usuario_id,
                    a.produto_id,
                    u.nome AS usuario_nome,
                    u.email AS usuario_email,
                    p.titulo AS produto_titulo,
                    p.tipo AS produto_tipo,
                    p.storage_dl_pdf,
                    p.storage_view_pdf,
                    p.storage_path_pdf,
                    p.storage_dl_audio,
                    p.storage_view_audio,
                    p.storage_view_audio_dir
                FROM acessos a
                JOIN usuarios u ON u.id = a.usuario_id
                JOIN produtos p ON p.id = a.produto_id
                WHERE a.status = "ativo"
                  AND a.data_liberacao IS NOT NULL
                  AND NOW() >= a.data_liberacao
                  AND (a.liberacao_email_status IS NULL OR a.liberacao_email_status <> "sucesso")
                ORDER BY a.data_liberacao ASC
                LIMIT ' . $limit;

        $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        if (!$rows) {
            JsonResponse::ok(['processed' => 0, 'message' => 'Nenhum acesso elegível']);
            return;
        }

        $processed = 0; $okCount = 0; $failCount = 0;
        foreach ($rows as $row) {
            $processed++;
            $ebookLink = null; $audioLink = null;
            $tipo = (string)$row['produto_tipo'];
            $usuarioId = (int)$row['usuario_id'];
            $produtoId = (int)$row['produto_id'];
            $acessoId = (int)$row['acesso_id'];
            if ($tipo === 'ebook') {
                $path = (string)($row['storage_dl_pdf'] ?: $row['storage_view_pdf'] ?: $row['storage_path_pdf'] ?: '');
                if ($path !== '' && is_file($path)) {
                    $tok = self::generateDownloadToken($usuarioId, $produtoId, 48);
                    if ($tok) { $ebookLink = rtrim(Config::appUrl(), '/') . '/api/downloads/file?token=' . $tok; }
                }
            } else if ($tipo === 'audio') {
                $dl = (string)($row['storage_dl_audio'] ?: '');
                if ($dl !== '' && is_file($dl)) {
                    $tok = self::generateDownloadToken($usuarioId, $produtoId, 48);
                    if ($tok) { $audioLink = rtrim(Config::appUrl(), '/') . '/api/downloads/file?token=' . $tok; }
                }
            }

            $subject = 'Liberação completa — ' . (string)$row['produto_titulo'];
            $html = self::buildEmailHtml($row, $ebookLink, $audioLink);
            $ok = Mailer::send((string)$row['usuario_email'], $subject, $html);
            if ($ok) {
                $okCount++;
                $pdo->prepare('UPDATE acessos SET liberacao_email_enviado_em = NOW(), liberacao_email_status = "sucesso", liberacao_email_tentativas = liberacao_email_tentativas + 1, liberacao_email_ultima_tentativa_em = NOW() WHERE id = ?')->execute([$acessoId]);
            } else {
                $failCount++;
                $pdo->prepare('UPDATE acessos SET liberacao_email_status = "falha", liberacao_email_tentativas = liberacao_email_tentativas + 1, liberacao_email_ultima_tentativa_em = NOW() WHERE id = ?')->execute([$acessoId]);
            }
        }

        JsonResponse::ok(['processed' => $processed, 'ok' => $okCount, 'fail' => $failCount]);
    }

    private static function generateDownloadToken(int $usuarioId, int $produtoId, int $hours = 48): ?string
    {
        try {
            $pdo = Database::pdo();
            $token = bin2hex(random_bytes(32));
            $stmt = $pdo->prepare('INSERT INTO download_tokens (usuario_id, produto_id, token, expira_em, criado_em) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? HOUR), NOW())');
            $stmt->execute([$usuarioId, $produtoId, $token, $hours]);
            return $token;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function buildEmailHtml(array $row, ?string $ebookLink, ?string $audioLink): string
    {
        $nome = $row['usuario_nome'] !== '' ? $row['usuario_nome'] : 'Cliente';
        $areaUrl = rtrim(Config::appUrl(), '/') . '/members/';
        $titulo = (string)$row['produto_titulo'];
        $html = '';
        $html .= '<p>Olá ' . htmlspecialchars((string)$nome) . ',</p>';
        $html .= '<p>Seu acesso completo ao produto <strong>' . htmlspecialchars((string)$titulo) . '</strong> foi liberado.</p>';
        $html .= '<p>Você pode acessar todo o conteúdo agora pela Área de Membros:</p>';
        $html .= '<p><a href="' . htmlspecialchars($areaUrl) . '">' . htmlspecialchars($areaUrl) . '</a></p>';
        if ($ebookLink) {
            $html .= '<p>Baixar seu eBook (link válido por 48h):<br/>';
            $html .= '<a href="' . htmlspecialchars($ebookLink) . '">' . htmlspecialchars($ebookLink) . '</a></p>';
        }
        if ($audioLink) {
            $html .= '<p>Baixar seus áudios (link válido por 48h):<br/>';
            $html .= '<a href="' . htmlspecialchars($audioLink) . '">' . htmlspecialchars($audioLink) . '</a></p>';
        }
        $html .= '<p>Se precisar de ajuda, responda este e-mail.</p>';
        $html .= '<p>— Eros Vitta</p>';
        return $html;
    }
}


