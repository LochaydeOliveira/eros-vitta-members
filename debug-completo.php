<?php
// Debug completo do projeto
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 DEBUG COMPLETO DO PROJETO</h1>";
echo "<hr>";

// 1. Verificar se os arquivos principais existem
echo "<h2>1. Verificação de Arquivos</h2>";
$arquivos = [
    'app/config.php',
    'app/db.php', 
    'app/auth.php',
    'app/routes.php',
    'views/dashboard.php',
    'views/header.php',
    'views/sidebar.php',
    'views/footer.php'
];

foreach ($arquivos as $arquivo) {
    if (file_exists($arquivo)) {
        echo "✅ $arquivo - EXISTE<br>";
    } else {
        echo "❌ $arquivo - NÃO EXISTE<br>";
    }
}

// 2. Verificar configuração
echo "<h2>2. Verificação de Configuração</h2>";
if (file_exists('app/config.php')) {
    require_once 'app/config.php';
    echo "✅ config.php carregado<br>";
    echo "BASE_URL: " . (defined('BASE_URL') ? BASE_URL : 'NÃO DEFINIDO') . "<br>";
    echo "VIEWS_PATH: " . (defined('VIEWS_PATH') ? VIEWS_PATH : 'NÃO DEFINIDO') . "<br>";
} else {
    echo "❌ config.php não encontrado<br>";
}

// 3. Verificar banco de dados
echo "<h2>3. Verificação de Banco de Dados</h2>";
if (file_exists('app/db.php')) {
    try {
        require_once 'app/db.php';
        $db = Database::getInstance();
        echo "✅ Conexão com banco estabelecida<br>";
        
        // Testar query simples
        $result = $db->fetch("SELECT COUNT(*) as total FROM users");
        echo "✅ Query de teste funcionou: " . $result['total'] . " usuários<br>";
        
    } catch (Exception $e) {
        echo "❌ Erro na conexão: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ db.php não encontrado<br>";
}

// 4. Verificar rotas
echo "<h2>4. Verificação de Rotas</h2>";
if (file_exists('app/routes.php')) {
    require_once 'app/routes.php';
    echo "✅ routes.php carregado<br>";
    
    // Verificar se a classe Router existe
    if (class_exists('Router')) {
        echo "✅ Classe Router existe<br>";
    } else {
        echo "❌ Classe Router não existe<br>";
    }
} else {
    echo "❌ routes.php não encontrado<br>";
}

// 5. Verificar sessão
echo "<h2>5. Verificação de Sessão</h2>";
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
echo "✅ Sessão iniciada<br>";
echo "Session ID: " . session_id() . "<br>";

// 6. Simular login para teste
echo "<h2>6. Simulação de Login</h2>";
$_SESSION['user'] = [
    'id' => 1,
    'nome' => 'Lochayde Guerreiro',
    'email' => 'lochaydeguerreiro@hotmail.com'
];
echo "✅ Usuário simulado na sessão<br>";

// 7. Testar query do dashboard
echo "<h2>7. Teste da Query do Dashboard</h2>";
if (isset($db)) {
    try {
        $materials = $db->fetchAll("
            SELECT DISTINCT m.*, up.purchase_date, up.item_type, up.hotmart_product_id
            FROM user_purchases up
            JOIN materials m ON up.material_id = m.id
            WHERE up.user_id = ? AND up.status = 'active' AND m.id IS NOT NULL
            ORDER BY up.purchase_date DESC
        ", [1]);
        
        echo "✅ Query executada com sucesso<br>";
        echo "Materiais encontrados: " . count($materials) . "<br>";
        
        foreach ($materials as $material) {
            echo "- ID: {$material['id']}, Título: {$material['titulo']}, Tipo: {$material['item_type']}<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Erro na query: " . $e->getMessage() . "<br>";
    }
}

// 8. Verificar arquivos de storage
echo "<h2>8. Verificação de Arquivos de Storage</h2>";
$storageFiles = [
    'storage/ebooks/o-guia-dos-5-toques-magicos.html',
    'storage/ebooks/libido-renovada.html',
    'storage/ebooks/sem-desejo-nunca-mais.html',
    'storage/ebooks/o-segredo-da-resistencia.html',
    'storage/audios/libido-renovada-o-plano-de-acao-de-21-dias-para-reacender-a-intimidade-e-a-libido.mp3'
];

foreach ($storageFiles as $file) {
    if (file_exists($file)) {
        echo "✅ $file - " . filesize($file) . " bytes<br>";
    } else {
        echo "❌ $file - NÃO ENCONTRADO<br>";
    }
}

// 9. Testar includes
echo "<h2>9. Teste de Includes</h2>";
if (file_exists('views/header.php')) {
    echo "✅ header.php existe<br>";
} else {
    echo "❌ header.php não existe<br>";
}

if (file_exists('views/sidebar.php')) {
    echo "✅ sidebar.php existe<br>";
} else {
    echo "❌ sidebar.php não existe<br>";
}

if (file_exists('views/footer.php')) {
    echo "✅ footer.php existe<br>";
} else {
    echo "❌ footer.php não existe<br>";
}

// 10. Diagnóstico final
echo "<h2>10. Diagnóstico Final</h2>";
$problemas = [];

if (!file_exists('app/config.php')) $problemas[] = "config.php não existe";
if (!file_exists('app/db.php')) $problemas[] = "db.php não existe";
if (!file_exists('app/routes.php')) $problemas[] = "routes.php não existe";
if (!file_exists('views/dashboard.php')) $problemas[] = "dashboard.php não existe";

if (empty($problemas)) {
    echo "✅ Todos os arquivos principais existem<br>";
    echo "✅ Sistema deve estar funcionando<br>";
    echo "<br><strong>TESTE O DASHBOARD:</strong><br>";
    echo "<a href='dashboard' style='color: #c67b54; font-size: 18px;'>🚀 ACESSAR DASHBOARD</a><br>";
} else {
    echo "❌ Problemas encontrados:<br>";
    foreach ($problemas as $problema) {
        echo "- $problema<br>";
    }
}

echo "<hr>";
echo "<p><strong>Se ainda não funcionar, execute este debug e me envie o resultado completo.</strong></p>";
?>
