<?php
declare(strict_types=1);

// CRON diário: envia e-mail pós D+7 para acessos liberados
// Uso sugerido no cPanel:
// /usr/local/bin/php -d detect_unicode=0 /home1/paymen58/erosvitta.com.br/cron/daily.php >/dev/null 2>&1

use App\Config;
use App\Database;
use App\Mail\Mailer;

require_once __DIR__ . '/../src/bootstrap.php';

date_default_timezone_set('America/Sao_Paulo');

function logLine(string $msg): void {
    $logDir = dirname(__DIR__) . '/logs';
    if (!is_dir($logDir)) { @mkdir($logDir, 0777, true); }
    $file = $logDir . '/cron_' . date('Y-m-d') . '.log';
    @file_put_contents($file, date('c') . ' ' . $msg . PHP_EOL, FILE_APPEND);
}

function generateDownloadToken(int $usuarioId, int $produtoId, int $hours = 48): ?string {
    try {
        $pdo = Database::pdo();
        $token = bin2hex(random_bytes(32));
        $stmt = $pdo->prepare('INSERT INTO download_tokens (usuario_id, produto_id, token, expira_em, criado_em) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? HOUR), NOW())');
        $stmt->execute([$usuarioId, $produtoId, $token, $hours]);
        return $token;
    } catch (\Throwable $e) {
        logLine('ERR generateDownloadToken: ' . $e->getMessage());
        return null;
    }
}

function buildEmailHtml(array $row, ?string $ebookLink, ?string $audioLink): string {
    $nome = $row['usuario_nome'] !== '' ? $row['usuario_nome'] : 'Cliente';
    $areaUrl = rtrim(Config::appUrl(), '/') . '/members/';
    $titulo = (string)$row['produto_titulo'];
    $html = '';
    $html .= '<p>Olá ' . htmlspecialchars($nome) . ',</p>';
    $html .= '<p>Seu acesso completo ao produto <strong>' . htmlspecialchars($titulo) . '</strong> foi liberado.</p>';
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

try {
    $pdo = Database::pdo();

    // Seleciona até 50 acessos elegíveis por execução
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
            LIMIT 50';

    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    if (!$rows) {
        logLine('Nenhum acesso elegível encontrado.');
        exit(0);
    }

    foreach ($rows as $row) {
        $acessoId = (int)$row['acesso_id'];
        $usuarioId = (int)$row['usuario_id'];
        $produtoId = (int)$row['produto_id'];
        $email = (string)$row['usuario_email'];
        $tipo = (string)$row['produto_tipo'];

        $ebookLink = null;
        $audioLink = null;

        // Se houver caminhos de download configurados e arquivos existirem, gera link temporário
        if ($tipo === 'ebook') {
            $path = (string)($row['storage_dl_pdf'] ?: $row['storage_view_pdf'] ?: $row['storage_path_pdf'] ?: '');
            if ($path !== '' && is_file($path)) {
                $tok = generateDownloadToken($usuarioId, $produtoId, 48);
                if ($tok) { $ebookLink = rtrim(Config::appUrl(), '/') . '/api/downloads/file?token=' . $tok; }
            }
        } else if ($tipo === 'audio') {
            $dl = (string)($row['storage_dl_audio'] ?: '');
            if ($dl !== '' && is_file($dl)) {
                $tok = generateDownloadToken($usuarioId, $produtoId, 48);
                if ($tok) { $audioLink = rtrim(Config::appUrl(), '/') . '/api/downloads/file?token=' . $tok; }
            }
        }

        $subject = 'Liberação completa — ' . (string)$row['produto_titulo'];
        $html = buildEmailHtml($row, $ebookLink, $audioLink);

        $ok = Mailer::send($email, $subject, $html);
        if ($ok) {
            $pdo->prepare('UPDATE acessos SET liberacao_email_enviado_em = NOW(), liberacao_email_status = "sucesso", liberacao_email_tentativas = liberacao_email_tentativas + 1, liberacao_email_ultima_tentativa_em = NOW() WHERE id = ?')
                ->execute([$acessoId]);
            logLine("OK envio acesso_id={$acessoId} email={$email}");
        } else {
            $pdo->prepare('UPDATE acessos SET liberacao_email_status = "falha", liberacao_email_tentativas = liberacao_email_tentativas + 1, liberacao_email_ultima_tentativa_em = NOW() WHERE id = ?')
                ->execute([$acessoId]);
            logLine("FAIL envio acesso_id={$acessoId} email={$email}");
        }
    }
} catch (\Throwable $e) {
    logLine('FATAL: ' . $e->getMessage());
    exit(1);
}


