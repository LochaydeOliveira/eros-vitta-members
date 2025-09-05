<?php
require_once __DIR__ . '/../includes/init.php';
finalizeInit();
requireLogin();
require_once __DIR__ . '/../includes/db.php';

// Permitir apenas administradores (campo users.usuario = 'Administrador')
$currentUserId = $_SESSION['user_id'] ?? 0;
$isAdmin = false;
try {
    $st = $pdo->prepare('SELECT usuario FROM users WHERE id = ?');
    $st->execute([$currentUserId]);
    $u = $st->fetch(PDO::FETCH_ASSOC);
    if ($u && strtolower((string)$u['usuario']) === 'administrador') {
        $isAdmin = true;
    }
} catch (Throwable $e) {
    $isAdmin = false;
}

if (!$isAdmin) {
    http_response_code(403);
    echo 'Acesso negado.';
    exit;
}

$sqlPath = realpath(__DIR__ . '/../../sql/paymen58_db_libido.sql');
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


