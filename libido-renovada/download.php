<?php
require_once __DIR__ . '/includes/init.php';
finalizeInit();
requireLogin();

header('X-Content-Type-Options: nosniff');

$user = getCurrentUser();
require_once __DIR__ . '/includes/db.php';

$allowedTypes = ['ebook','audio'];
$type = $_GET['type'] ?? 'ebook';
$fileParam = $_GET['file'] ?? '';

if (!in_array($type, $allowedTypes, true) || $fileParam === '') {
    http_response_code(400);
    echo 'Parâmetros inválidos.';
    exit;
}

// Mapear caminhos por tipo (todos sob ../assets)
// Definir diretórios base por tipo
if ($type === 'ebook') {
    $baseDir = realpath(__DIR__ . '/e-books');
} else {
    $baseDir = realpath(__DIR__ . '/audios');
}

if ($baseDir === false) {
    http_response_code(500);
    echo 'Configuração inválida.';
    exit;
}

// Normalizar caminho solicitado e impedir path traversal
$requested = str_replace(['..', "\0"], '', $fileParam);
$fullPath = realpath($baseDir . DIRECTORY_SEPARATOR . $requested);

if ($fullPath === false || strpos($fullPath, $baseDir) !== 0 || !is_file($fullPath)) {
    http_response_code(404);
    echo 'Arquivo não encontrado.';
    exit;
}

// Verificar janela de 7 dias desde a última compra aprovada
$canDownload = false;
try {
    $stmt = $pdo->prepare("SELECT MAX(approved_at) AS last_approved FROM purchases WHERE user_id = ? AND status = 'approved'");
    $stmt->execute([(int)$user['id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && !empty($row['last_approved'])) {
        $approvedAt = strtotime($row['last_approved']);
        $diffDays = floor((time() - $approvedAt) / 86400);
        $canDownload = ($diffDays >= 7);
    }
} catch (Throwable $e) {
    error_log('Erro ao verificar liberação de download: ' . $e->getMessage());
}

if (!$canDownload) {
    http_response_code(403);
    echo 'Download disponível somente após 7 dias da aprovação da compra.';
    exit;
}

// Forçar download seguro
$fileName = basename($fullPath);
$mime = 'application/octet-stream';
if (str_ends_with(strtolower($fileName), '.pdf')) {
    $mime = 'application/pdf';
}

if (!headers_sent()) {
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($fullPath));
    header('Content-Disposition: attachment; filename="' . rawurlencode($fileName) . '"');
    header('Cache-Control: private, no-transform, no-store, must-revalidate');
}

// Enviar arquivo
readfile($fullPath);
exit;


