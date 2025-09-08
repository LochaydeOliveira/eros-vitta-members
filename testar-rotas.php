<?php
// Teste de todas as rotas
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🛣️ TESTE DE ROTAS</h1>";
echo "<hr>";

// Simular diferentes URLs
$urls = [
    '',
    'dashboard',
    'login',
    'logout',
    'libido-renovada',
    'upsell',
    'libido-renovada-up',
    'downsell',
    'libido-renovada-down',
    'obrigado',
    'libido-renovada-obrigado',
    'debug-dashboard',
    'teste-dashboard',
    'dashboard-funcionando',
    'dashboard-simples',
    'verificar-arquivos',
    'debug-dashboard-completo'
];

echo "<h2>Testando Rotas:</h2>";

foreach ($urls as $url) {
    echo "<div style='margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 4px;'>";
    echo "<strong>URL:</strong> " . ($url ?: '(raiz)') . "<br>";
    
    // Simular a requisição
    $_GET['url'] = $url;
    
    try {
        // Verificar se o arquivo index.php existe
        if (file_exists('index.php')) {
            echo "✅ index.php existe<br>";
            
            // Verificar se as dependências existem
            if (file_exists('app/config.php')) {
                echo "✅ config.php existe<br>";
            } else {
                echo "❌ config.php não existe<br>";
            }
            
            if (file_exists('app/routes.php')) {
                echo "✅ routes.php existe<br>";
            } else {
                echo "❌ routes.php não existe<br>";
            }
            
        } else {
            echo "❌ index.php não existe<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Erro: " . $e->getMessage() . "<br>";
    }
    
    echo "</div>";
}

echo "<hr>";
echo "<h2>Teste Manual de Rotas:</h2>";
echo "<p>Teste estas URLs manualmente:</p>";

foreach ($urls as $url) {
    $fullUrl = "https://erosvitta.com.br/" . ($url ?: '');
    echo "<a href='$fullUrl' target='_blank' style='display: block; margin: 5px 0; padding: 5px; background: #f8f9fa; border-radius: 4px; text-decoration: none; color: #333;'>";
    echo "🔗 $fullUrl";
    echo "</a>";
}

echo "<hr>";
echo "<h2>Diagnóstico:</h2>";
echo "<p>Se todas as rotas retornarem 404, o problema está no arquivo <strong>index.php</strong> ou no <strong>.htaccess</strong>.</p>";
echo "<p>Se algumas rotas funcionarem e outras não, o problema está no arquivo <strong>app/routes.php</strong>.</p>";
?>
