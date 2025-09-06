<?php
echo "<h1>Teste da Nova Área de Membros</h1>";

echo "<h2>1. Teste de Configuração</h2>";
try {
    require_once 'config.php';
    echo "✅ Config carregado<br>";
    echo "DB_NAME: " . DB_NAME . "<br>";
    echo "WEBHOOK_SECRET: " . (defined('WEBHOOK_SHARED_SECRET') ? 'Definido' : 'Não definido') . "<br>";
} catch (Exception $e) {
    echo "❌ Erro no config: " . $e->getMessage() . "<br>";
}

echo "<h2>2. Teste de Conexão DB</h2>";
try {
    require_once 'includes/db.php';
    global $pdo;
    if ($pdo) {
        echo "✅ Conexão DB OK<br>";
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
        $result = $stmt->fetch();
        echo "Usuários no banco: " . $result['total'] . "<br>";
    } else {
        echo "❌ Conexão DB falhou<br>";
    }
} catch (Exception $e) {
    echo "❌ Erro DB: " . $e->getMessage() . "<br>";
}

echo "<h2>3. Teste de Diretórios</h2>";
$dirs = ['e-books', 'audios', 'logs', 'includes'];
foreach ($dirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (is_dir($path)) {
        echo "✅ $dir existe<br>";
    } else {
        echo "❌ $dir não existe<br>";
    }
}

echo "<h2>4. Teste de Arquivos</h2>";
$files = ['login.php', 'index.php', 'logout.php', 'view.php', 'download.php', 'webhook.php'];
foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "✅ $file existe<br>";
    } else {
        echo "❌ $file não existe<br>";
    }
}

echo "<h2>5. Teste de E-books</h2>";
$ebooks_dir = __DIR__ . '/e-books/';
if (is_dir($ebooks_dir)) {
    $files = scandir($ebooks_dir);
    $pdfs = array_filter($files, function($file) {
        return pathinfo($file, PATHINFO_EXTENSION) === 'pdf';
    });
    echo "E-books encontrados: " . count($pdfs) . "<br>";
    foreach ($pdfs as $pdf) {
        echo "- " . $pdf . "<br>";
    }
} else {
    echo "❌ Pasta e-books não existe<br>";
}

echo "<h2>6. Links de Teste</h2>";
echo '<a href="login.php">Login</a><br>';
echo '<a href="status.php">Status PHP</a><br>';
echo '<a href="status.txt">Status TXT</a><br>';
echo '<a href="tools/diagnostics.php">Diagnósticos</a><br>';

echo "<h2>7. Webhook URLs</h2>";
echo "Webhook principal: <a href='webhook.php'>webhook.php</a><br>";
echo "Webhook Hotmart: <a href='hotmart-webhook.php'>hotmart-webhook.php</a><br>";

echo "<p><strong>Teste concluído!</strong></p>";
?>
