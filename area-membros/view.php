<?php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Verificar se está logado
requireLogin();

$type = $_GET['type'] ?? '';
$file = $_GET['file'] ?? '';

if (!in_array($type, ['ebook', 'audio']) || empty($file)) {
    http_response_code(400);
    die('Parâmetros inválidos');
}

// Determinar diretório baseado no tipo
$base_dir = $type === 'ebook' ? __DIR__ . '/e-books/' : __DIR__ . '/audios/';
$file_path = $base_dir . basename($file);

// Verificar se arquivo existe
if (!file_exists($file_path)) {
    http_response_code(404);
    die('Arquivo não encontrado');
}

// Verificar extensão do arquivo
$allowed_extensions = $type === 'ebook' ? ['pdf'] : ['mp3', 'wav', 'm4a', 'ogg'];
$file_extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

if (!in_array($file_extension, $allowed_extensions)) {
    http_response_code(400);
    die('Tipo de arquivo não permitido');
}

// Configurar headers para visualização inline
$mime_types = [
    'pdf' => 'application/pdf',
    'mp3' => 'audio/mpeg',
    'wav' => 'audio/wav',
    'm4a' => 'audio/mp4',
    'ogg' => 'audio/ogg'
];

$mime_type = $mime_types[$file_extension] ?? 'application/octet-stream';

header('Content-Type: ' . $mime_type);
header('Content-Disposition: inline; filename="' . basename($file) . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: private, max-age=3600');

// Enviar arquivo
readfile($file_path);
?>
