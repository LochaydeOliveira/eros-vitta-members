<?php
require_once __DIR__ . '/includes/init.php';
finalizeInit();
requireLogin();

header('X-Content-Type-Options: nosniff');

$user = getCurrentUser();
$type = $_GET['type'] ?? 'ebook';
$fileParam = $_GET['file'] ?? '';

if ($type !== 'ebook' || $fileParam === '') {
    http_response_code(400);
    echo 'Parâmetros inválidos.';
    exit;
}

$baseDir = realpath(__DIR__ . '/e-books');
if ($baseDir === false) {
    http_response_code(500);
    echo 'Configuração inválida.';
    exit;
}

$requested = str_replace(['..', "\0"], '', $fileParam);
$fullPath = realpath($baseDir . DIRECTORY_SEPARATOR . $requested);

if ($fullPath === false || strpos($fullPath, $baseDir) !== 0 || !is_file($fullPath)) {
    http_response_code(404);
    echo 'Arquivo não encontrado.';
    exit;
}

// Exibir inline com Content-Disposition inline
if (!headers_sent()) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . rawurlencode(basename($fullPath)) . '"');
    header('Cache-Control: private, no-transform, no-store, must-revalidate');
}

readfile($fullPath);
exit;


