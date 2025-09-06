<?php
require_once __DIR__ . '/../includes/init.php';
finalizeInit();
require_once __DIR__ . '/../includes/db.php';

// Autorização: via token (GET token igual ao WEBHOOK_SHARED_SECRET) OU usuário logado Administrador
$token = $_GET['token'] ?? '';
$authorized = false;
if (!empty($token) && defined('WEBHOOK_SHARED_SECRET') && hash_equals((string)WEBHOOK_SHARED_SECRET, (string)$token)) {
    $authorized = true;
} else {
    // fallback: requer login + perfil administrador
    if (!function_exists('requireLogin')) {
        // Se a função não existir no contexto atual, negar
        http_response_code(403);
        echo 'Acesso negado (login não disponível).';
        exit;
    }
    requireLogin();
    $currentUserId = $_SESSION['user_id'] ?? 0;
    try {
        $st = $pdo->prepare('SELECT usuario FROM users WHERE id = ?');
        $st->execute([$currentUserId]);
        $u = $st->fetch(PDO::FETCH_ASSOC);
        if ($u && strtolower((string)$u['usuario']) === 'administrador') {
            $authorized = true;
        }
    } catch (Throwable $e) {
        $authorized = false;
    }
}

if (!$authorized) {
    http_response_code(403);
    echo 'Acesso negado.';
    exit;
}

// Usar script idempotente para evitar erros de "já existe"
$sqlPath = realpath(__DIR__ . '/../../sql/update_idempotent.sql');
if ($sqlPath === false || !is_file($sqlPath)) {
    http_response_code(404);
    echo 'Arquivo SQL não encontrado.';
    exit;
}

@set_time_limit(300);
$sql = file_get_contents($sqlPath);
if ($sql === false) {
    http_response_code(500);
    echo 'Falha ao ler o arquivo SQL.';
    exit;
}

// Normalizar quebras de linha
$sql = str_replace(["\r\n", "\r"], "\n", $sql);

// Opcional: remover comentários simples para evitar falhas no split
$lines = explode("\n", $sql);
$filtered = [];
$inBlockComment = false;
foreach ($lines as $line) {
    $trim = ltrim($line);
    if ($inBlockComment) {
        if (strpos($trim, '*/') !== false) {
            $inBlockComment = false;
        }
        continue;
    }
    if (strpos($trim, '/*') === 0) {
        if (strpos($trim, '*/') === false) {
            $inBlockComment = true;
        }
        continue;
    }
    if (strpos($trim, '--') === 0 || $trim === '') continue;
    $filtered[] = $line;
}
$sql = implode("\n", $filtered);

// Dividir em statements por ; em final de linha
$statements = preg_split('/;\s*\n/', $sql);
$executed = 0;
$errors = [];

foreach ($statements as $stmt) {
    $stmt = trim($stmt);
    if ($stmt === '') continue;
    try {
        $pdo->exec($stmt);
        $executed++;
    } catch (Throwable $e) {
        $errors[] = ['stmt' => $stmt, 'error' => $e->getMessage()];
    }
}

header('Content-Type: text/plain; charset=UTF-8');
echo "Executadas {$executed} instruções SQL.\n";
if (!empty($errors)) {
    echo "\nOcorreram erros em " . count($errors) . " instruções:\n";
    foreach ($errors as $i => $err) {
        echo "\n#" . ($i+1) . " Erro: " . $err['error'] . "\n";
        echo "Statement:\n" . substr($err['stmt'], 0, 5000) . "\n"; // limitar tamanho
    }
    http_response_code(207);
}
exit;


