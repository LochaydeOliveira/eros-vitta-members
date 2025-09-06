<?php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Verificar se está logado
requireLogin();

$user = getCurrentUser();
$type = $_GET['type'] ?? '';
$file = $_GET['file'] ?? '';

if (!in_array($type, ['ebook', 'audio']) || empty($file)) {
    http_response_code(400);
    die('Parâmetros inválidos');
}

// Verificar se o usuário tem permissão para download
$stmt = $pdo->prepare("
    SELECT approved_at, created_at 
    FROM purchases 
    WHERE user_id = ? AND status = 'approved' 
    ORDER BY approved_at DESC 
    LIMIT 1
");
$stmt->execute([$user['id']]);
$purchase = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$purchase || !$purchase['approved_at']) {
    http_response_code(403);
    die('Compra não encontrada ou não aprovada');
}

// Verificar se já passou o período de 7 dias
$approved_date = new DateTime($purchase['approved_at']);
$now = new DateTime();
$days_diff = $now->diff($approved_date)->days;

if ($days_diff < DOWNLOAD_DELAY_DAYS) {
    http_response_code(403);
    die('Download ainda não liberado. Aguarde ' . (DOWNLOAD_DELAY_DAYS - $days_diff) . ' dia(s).');
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

// Configurar headers para download
$mime_types = [
    'pdf' => 'application/pdf',
    'mp3' => 'audio/mpeg',
    'wav' => 'audio/wav',
    'm4a' => 'audio/mp4',
    'ogg' => 'audio/ogg'
];

$mime_type = $mime_types[$file_extension] ?? 'application/octet-stream';

header('Content-Type: ' . $mime_type);
header('Content-Disposition: attachment; filename="' . basename($file) . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: private, max-age=3600');

// Log do download
app_log("Download realizado: {$user['email']} - {$type}:{$file}");

// Enviar arquivo
readfile($file_path);
?>
