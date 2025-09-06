<?php
require_once __DIR__ . '/../conexao.php';
header('Content-Type: text/plain; charset=UTF-8');

echo "Diagnostics - Libido Renovado\n\n";
echo "Date: " . date('c') . "\n";
echo "IP: " . ($_SERVER['REMOTE_ADDR'] ?? '') . "\n";
echo "XFF: " . ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '') . "\n";
echo "UA: " . ($_SERVER['HTTP_USER_AGENT'] ?? '') . "\n";
echo "URI: " . ($_SERVER['REQUEST_URI'] ?? '') . "\n\n";

// Teste de DB
try {
    global $pdo;
    $stmt = $pdo->query('SELECT 1 as ok');
    $row = $stmt->fetch();
    echo "DB: OK (" . ($row['ok'] ?? '?') . ")\n";
} catch (Throwable $e) {
    echo "DB: ERRO - " . $e->getMessage() . "\n";
}

// Teste de sessão
echo "Session: status=" . session_status() . " id=" . session_id() . "\n";

// Testar escrita de log
try {
    app_log('DIAG: teste de log');
    echo "Log: OK (escrito em logs)\n";
} catch (Throwable $e) {
    echo "Log: ERRO - " . $e->getMessage() . "\n";
}

// Verificar permissões de diretórios importantes
$paths = [
    __DIR__ . '/..',
    __DIR__ . '/../logs',
    __DIR__ . '/../e-books',
    __DIR__ . '/../audios',
];
foreach ($paths as $p) {
    $exists = is_dir($p) ? 'DIR' : (is_file($p) ? 'FILE' : 'NONE');
    echo "Path: $p => $exists\n";
}

echo "\nDone.\n";
exit;

