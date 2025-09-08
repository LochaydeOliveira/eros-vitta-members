<?php
session_start();
require_once 'app/config.php';
require_once 'app/db.php';

// Forçar login do usuário de teste
$_SESSION['user'] = [
    'id' => 1,
    'nome' => 'Lochayde Guerreiro',
    'email' => 'lochaydeguerreiro@hotmail.com'
];

$db = Database::getInstance();
$userId = $_SESSION['user']['id'];

echo "<h1>🔍 Debug Completo do Dashboard</h1>";
echo "<h2>Usuário: " . $_SESSION['user']['nome'] . " (ID: $userId)</h2>";

// 1. Verificar se o usuário existe
echo "<h3>1. Verificação do Usuário</h3>";
$user = $db->fetch("SELECT * FROM users WHERE id = ?", [$userId]);
if ($user) {
    echo "✅ Usuário encontrado: " . $user['nome'] . " (" . $user['email'] . ")<br>";
} else {
    echo "❌ Usuário não encontrado<br>";
}

// 2. Verificar compras do usuário
echo "<h3>2. Compras do Usuário</h3>";
$purchases = $db->fetchAll("SELECT * FROM user_purchases WHERE user_id = ?", [$userId]);
echo "Total de compras: " . count($purchases) . "<br>";
foreach ($purchases as $purchase) {
    echo "- ID: {$purchase['id']}, Produto: {$purchase['hotmart_product_id']}, Tipo: {$purchase['item_type']}, Status: {$purchase['status']}<br>";
}

// 3. Verificar mapeamento de produtos
echo "<h3>3. Mapeamento de Produtos</h3>";
$mappings = $db->fetchAll("SELECT * FROM product_material_mapping");
echo "Total de mapeamentos: " . count($mappings) . "<br>";
foreach ($mappings as $mapping) {
    echo "- Produto: {$mapping['hotmart_product_id']} -> Material: {$mapping['material_id']}<br>";
}

// 4. Verificar materiais
echo "<h3>4. Materiais Disponíveis</h3>";
$materials = $db->fetchAll("SELECT * FROM materials ORDER BY id");
echo "Total de materiais: " . count($materials) . "<br>";
foreach ($materials as $material) {
    echo "- ID: {$material['id']}, Título: {$material['titulo']}, Caminho: {$material['caminho']}<br>";
}

// 5. Testar a query do dashboard
echo "<h3>5. Query do Dashboard (Sistema Novo)</h3>";
$dashboardMaterials = $db->fetchAll("
    SELECT DISTINCT m.*, up.purchase_date, up.item_type, up.hotmart_product_id
    FROM user_purchases up
    LEFT JOIN product_material_mapping pmm ON up.hotmart_product_id = pmm.hotmart_product_id
    LEFT JOIN materials m ON pmm.material_id = m.id
    WHERE up.user_id = ? AND up.status = 'active' AND m.id IS NOT NULL
    ORDER BY up.purchase_date DESC
", [$userId]);

echo "Materiais encontrados (sistema novo): " . count($dashboardMaterials) . "<br>";
foreach ($dashboardMaterials as $material) {
    echo "- ID: {$material['id']}, Título: {$material['titulo']}, Tipo: {$material['item_type']}<br>";
}

// 6. Testar sistema antigo
echo "<h3>6. Query do Dashboard (Sistema Antigo)</h3>";
$legacyMaterials = $db->fetchAll("
    SELECT m.*, um.liberado_em as purchase_date, 'legacy' as item_type, 'LEGACY' as hotmart_product_id
    FROM user_materials um
    JOIN materials m ON um.material_id = m.id
    WHERE um.user_id = ?
    ORDER BY um.liberado_em DESC
", [$userId]);

echo "Materiais encontrados (sistema antigo): " . count($legacyMaterials) . "<br>";
foreach ($legacyMaterials as $material) {
    echo "- ID: {$material['id']}, Título: {$material['titulo']}, Tipo: {$material['item_type']}<br>";
}

// 7. Verificar se os arquivos existem
echo "<h3>7. Verificação de Arquivos</h3>";
$expectedFiles = [
    'ebooks/o-guia-dos-5-toques-magicos.html',
    'ebooks/libido-renovada.html',
    'ebooks/sem-desejo-nunca-mais.html',
    'ebooks/o-segredo-da-resistencia.html',
    'audios/libido-renovada-o-plano-de-acao-de-21-dias-para-reacender-a-intimidade-e-a-libido.mp3'
];

foreach ($expectedFiles as $file) {
    $fullPath = __DIR__ . '/storage/' . $file;
    if (file_exists($fullPath)) {
        echo "✅ $file - " . filesize($fullPath) . " bytes<br>";
    } else {
        echo "❌ $file - NÃO ENCONTRADO<br>";
    }
}

// 8. Diagnóstico final
echo "<h3>8. Diagnóstico Final</h3>";
if (empty($dashboardMaterials) && empty($legacyMaterials)) {
    echo "❌ PROBLEMA: Nenhum material encontrado em nenhum sistema<br>";
    echo "🔧 SOLUÇÃO: Execute o script 'adicionar-materiais-simples.sql'<br>";
} elseif (!empty($dashboardMaterials)) {
    echo "✅ Sistema novo funcionando - " . count($dashboardMaterials) . " materiais encontrados<br>";
} elseif (!empty($legacyMaterials)) {
    echo "✅ Sistema antigo funcionando - " . count($legacyMaterials) . " materiais encontrados<br>";
}

echo "<hr>";
echo "<h3>🔧 Scripts para Executar:</h3>";
echo "1. <a href='adicionar-materiais-simples.sql' target='_blank'>adicionar-materiais-simples.sql</a><br>";
echo "2. <a href='corrigir-caminhos-arquivos.sql' target='_blank'>corrigir-caminhos-arquivos.sql</a><br>";
echo "3. <a href='testar-dashboard-final.sql' target='_blank'>testar-dashboard-final.sql</a><br>";
?>
