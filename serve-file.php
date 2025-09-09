<?php
session_start();
require_once 'app/config.php';
require_once 'app/db.php';
require_once 'app/auth.php';

// Verifica se o usuário está logado
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(403);
    die('Acesso negado');
}

$materialId = $_GET['id'] ?? null;
$pathParam = $_GET['path'] ?? null; // caminho relativo dentro de storage

if (!$materialId && !$pathParam) {
    http_response_code(400);
    die('Parâmetro ausente');
}

$user = $auth->getCurrentUser();
$db = Database::getInstance();

if ($materialId) {
    // Busca o material do usuário (aceita também via user_purchases + mapeamento)
    $material = $db->fetch(
        "SELECT m.* FROM materials m WHERE m.id = ?",
        [$materialId]
    );
    if (!$material) {
        http_response_code(404);
        die('Material não encontrado');
    }
    $filePath = STORAGE_PATH . '/' . $material['caminho'];
} else {
    $relative = ltrim($pathParam, '/');
    $filePath = realpath(STORAGE_PATH . '/' . $relative);
    $storageRoot = realpath(STORAGE_PATH);
    if (!$filePath || strpos($filePath, $storageRoot) !== 0) {
        http_response_code(400);
        die('Caminho inválido');
    }
}

if (!file_exists($filePath)) {
    http_response_code(404);
    die('Arquivo não encontrado');
}

// Determina o tipo MIME
$extension = pathinfo($filePath, PATHINFO_EXTENSION);
$mimeTypes = [
    'pdf' => 'application/pdf',
    'mp4' => 'video/mp4',
    'mp3' => 'audio/mpeg',
    'wav' => 'audio/wav',
    'html' => 'text/html',
    'css' => 'text/css',
    'js' => 'application/javascript'
];

$mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';

// Define headers
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, max-age=3600');

// Para arquivos de mídia, permite range requests
if (in_array($extension, ['mp4', 'mp3', 'wav'])) {
    header('Accept-Ranges: bytes');
}

// Serve o arquivo
readfile($filePath);
?>
