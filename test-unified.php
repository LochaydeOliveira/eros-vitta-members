<?php
echo "<h1>✅ PHP FUNCIONANDO COM .HTACCESS UNIFICADO!</h1>";
echo "<p>Data: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>SAPI: " . php_sapi_name() . "</p>";
echo "<p>Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "</p>";
echo "<p>Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "</p>";
echo "<p>Script Name: " . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "</p>";
echo "<p>Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "</p>";
echo "<p>HTTPS: " . (isset($_SERVER['HTTPS']) ? 'SIM' : 'NÃO') . "</p>";

echo "<h2>Teste de Escrita:</h2>";
$test_file = __DIR__ . '/test-write.txt';
if (file_put_contents($test_file, 'Teste de escrita - ' . date('Y-m-d H:i:s'))) {
    echo "<p>✅ Escrita OK</p>";
    unlink($test_file);
} else {
    echo "<p>❌ Escrita FALHOU</p>";
}

echo "<h2>Teste de Diretórios:</h2>";
$dirs = ['area-membros', 'membros', 'libido-renovada'];
foreach ($dirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (is_dir($path)) {
        echo "<p>✅ $dir existe</p>";
    } else {
        echo "<p>❌ $dir não existe</p>";
    }
}

echo "<h2>Teste de Arquivos PHP:</h2>";
$php_files = [
    'test-minimal.php',
    'area-membros/test-minimal.php',
    'membros/test-minimal.php',
    'libido-renovada/test-minimal.php'
];

foreach ($php_files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "<p>✅ $file existe</p>";
    } else {
        echo "<p>❌ $file não existe</p>";
    }
}

echo "<h2>Links de Teste:</h2>";
echo '<p><a href="test-minimal.php">Teste Mínimo (Raiz)</a></p>';
echo '<p><a href="area-membros/test-minimal.php">Teste Mínimo (Area-Membros)</a></p>';
echo '<p><a href="membros/test-minimal.php">Teste Mínimo (Membros)</a></p>';
echo '<p><a href="libido-renovada/test-minimal.php">Teste Mínimo (Libido-Renovada)</a></p>';

echo "<h2>Área de Membros:</h2>";
echo '<p><a href="area-membros/index.html">Dashboard HTML</a></p>';
echo '<p><a href="area-membros/login.html">Login HTML</a></p>';
echo '<p><a href="area-membros/debug.html">Debug HTML</a></p>';

echo "<h2>Status:</h2>";
echo '<p><a href="area-membros/status.txt">Status TXT</a></p>';
echo '<p><a href="membros/status.txt">Status TXT (Membros)</a></p>';
echo '<p><a href="libido-renovada/status.txt">Status TXT (Libido-Renovada)</a></p>';

echo "<h2>FIM DO TESTE</h2>";
?>
