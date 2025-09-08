<?php
echo "PHP está funcionando!<br>";
echo "Data/Hora: " . date('Y-m-d H:i:s') . "<br>";
echo "Versão PHP: " . phpversion() . "<br>";

// Testar se os arquivos existem
$arquivos = [
    'app/config.php',
    'app/db.php',
    'app/routes.php',
    'views/dashboard.php',
    'index.php',
    '.htaccess'
];

echo "<h2>Verificação de Arquivos:</h2>";
foreach ($arquivos as $arquivo) {
    if (file_exists($arquivo)) {
        echo "✅ $arquivo - EXISTE<br>";
    } else {
        echo "❌ $arquivo - NÃO EXISTE<br>";
    }
}

// Testar configuração
echo "<h2>Teste de Configuração:</h2>";
if (file_exists('app/config.php')) {
    try {
        require_once 'app/config.php';
        echo "✅ config.php carregado<br>";
        echo "BASE_URL: " . (defined('BASE_URL') ? BASE_URL : 'NÃO DEFINIDO') . "<br>";
    } catch (Exception $e) {
        echo "❌ Erro ao carregar config.php: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ config.php não encontrado<br>";
}

// Testar banco
echo "<h2>Teste de Banco:</h2>";
if (file_exists('app/db.php')) {
    try {
        require_once 'app/db.php';
        $db = Database::getInstance();
        echo "✅ Conexão com banco estabelecida<br>";
    } catch (Exception $e) {
        echo "❌ Erro na conexão: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ db.php não encontrado<br>";
}
?>
