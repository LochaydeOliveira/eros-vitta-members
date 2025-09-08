<?php
// Script de debug para investigar o problema do dashboard
session_start();
require_once 'app/config.php';
require_once 'app/db.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['user']['id'])) {
    die('Usuário não está logado');
}

$userId = $_SESSION['user']['id'];
$db = Database::getInstance();

echo "<h1>🔍 Debug do Dashboard</h1>";
echo "<h2>Usuário: " . $_SESSION['user']['nome'] . " (ID: $userId)</h2>";

// 1. Verificar se o usuário existe
echo "<h3>1. Verificação do Usuário</h3>";
$user = $db->fetch("SELECT * FROM users WHERE id = ?", [$userId]);
if ($user) {
    echo "✅ Usuário encontrado: " . $user['nome'] . " (" . $user['email'] . ")<br>";
} else {
    echo "❌ Usuário não encontrado<br>";
    exit;
}

// 2. Verificar user_purchases
echo "<h3>2. Verificação de user_purchases</h3>";
$purchases = $db->fetchAll("SELECT * FROM user_purchases WHERE user_id = ?", [$userId]);
echo "Total de compras: " . count($purchases) . "<br>";
if (!empty($purchases)) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Product ID</th><th>Item Type</th><th>Status</th><th>Material ID</th></tr>";
    foreach ($purchases as $purchase) {
        echo "<tr>";
        echo "<td>" . $purchase['id'] . "</td>";
        echo "<td>" . $purchase['hotmart_product_id'] . "</td>";
        echo "<td>" . $purchase['item_type'] . "</td>";
        echo "<td>" . $purchase['status'] . "</td>";
        echo "<td>" . ($purchase['material_id'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ Nenhuma compra encontrada<br>";
}

// 3. Verificar user_materials (sistema antigo)
echo "<h3>3. Verificação de user_materials (sistema antigo)</h3>";
$userMaterials = $db->fetchAll("SELECT * FROM user_materials WHERE user_id = ?", [$userId]);
echo "Total de materiais no sistema antigo: " . count($userMaterials) . "<br>";
if (!empty($userMaterials)) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Material ID</th><th>Liberado em</th></tr>";
    foreach ($userMaterials as $um) {
        echo "<tr>";
        echo "<td>" . $um['id'] . "</td>";
        echo "<td>" . $um['material_id'] . "</td>";
        echo "<td>" . $um['liberado_em'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ Nenhum material no sistema antigo<br>";
}

// 4. Verificar product_material_mapping
echo "<h3>4. Verificação de product_material_mapping</h3>";
$mappings = $db->fetchAll("SELECT * FROM product_material_mapping");
echo "Total de mapeamentos: " . count($mappings) . "<br>";
if (!empty($mappings)) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Hotmart Product ID</th><th>Material ID</th><th>Type</th></tr>";
    foreach ($mappings as $mapping) {
        echo "<tr>";
        echo "<td>" . $mapping['id'] . "</td>";
        echo "<td>" . $mapping['hotmart_product_id'] . "</td>";
        echo "<td>" . ($mapping['material_id'] ?? 'NULL') . "</td>";
        echo "<td>" . $mapping['material_type'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 5. Verificar materials
echo "<h3>5. Verificação de materials</h3>";
$materials = $db->fetchAll("SELECT * FROM materials");
echo "Total de materiais: " . count($materials) . "<br>";
if (!empty($materials)) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Título</th><th>Tipo</th><th>Ativo</th></tr>";
    foreach ($materials as $material) {
        echo "<tr>";
        echo "<td>" . $material['id'] . "</td>";
        echo "<td>" . $material['titulo'] . "</td>";
        echo "<td>" . $material['tipo'] . "</td>";
        echo "<td>" . ($material['is_active'] ? 'Sim' : 'Não') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 6. Testar a query do dashboard
echo "<h3>6. Teste da Query do Dashboard</h3>";
$dashboardQuery = "
    SELECT DISTINCT m.*, up.purchase_date, up.item_type
    FROM user_purchases up
    LEFT JOIN product_material_mapping pmm ON up.hotmart_product_id = pmm.hotmart_product_id
    LEFT JOIN materials m ON pmm.material_id = m.id
    WHERE up.user_id = ? AND up.status = 'active' AND m.id IS NOT NULL
    ORDER BY up.purchase_date DESC
";

$dashboardMaterials = $db->fetchAll($dashboardQuery, [$userId]);
echo "Materiais encontrados pela query do dashboard: " . count($dashboardMaterials) . "<br>";

if (!empty($dashboardMaterials)) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Título</th><th>Tipo</th><th>Item Type</th><th>Purchase Date</th></tr>";
    foreach ($dashboardMaterials as $material) {
        echo "<tr>";
        echo "<td>" . $material['id'] . "</td>";
        echo "<td>" . $material['titulo'] . "</td>";
        echo "<td>" . $material['tipo'] . "</td>";
        echo "<td>" . $material['item_type'] . "</td>";
        echo "<td>" . $material['purchase_date'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ Nenhum material encontrado pela query do dashboard<br>";
}

// 7. Testar query do sistema antigo
echo "<h3>7. Teste da Query do Sistema Antigo</h3>";
$legacyQuery = "
    SELECT m.*, um.liberado_em as purchase_date, 'legacy' as item_type
    FROM user_materials um
    JOIN materials m ON um.material_id = m.id
    WHERE um.user_id = ?
    ORDER BY um.liberado_em DESC
";

$legacyMaterials = $db->fetchAll($legacyQuery, [$userId]);
echo "Materiais encontrados pelo sistema antigo: " . count($legacyMaterials) . "<br>";

if (!empty($legacyMaterials)) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Título</th><th>Tipo</th><th>Item Type</th><th>Purchase Date</th></tr>";
    foreach ($legacyMaterials as $material) {
        echo "<tr>";
        echo "<td>" . $material['id'] . "</td>";
        echo "<td>" . $material['titulo'] . "</td>";
        echo "<td>" . $material['tipo'] . "</td>";
        echo "<td>" . $material['item_type'] . "</td>";
        echo "<td>" . $material['purchase_date'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ Nenhum material encontrado pelo sistema antigo<br>";
}

// 8. Verificar upsell_package_materials
echo "<h3>8. Verificação de upsell_package_materials</h3>";
$upsellMaterials = $db->fetchAll("SELECT * FROM upsell_package_materials");
echo "Total de materiais de upsell: " . count($upsellMaterials) . "<br>";
if (!empty($upsellMaterials)) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Hotmart Product ID</th><th>Material ID</th><th>Type</th></tr>";
    foreach ($upsellMaterials as $um) {
        echo "<tr>";
        echo "<td>" . $um['id'] . "</td>";
        echo "<td>" . $um['hotmart_product_id'] . "</td>";
        echo "<td>" . $um['material_id'] . "</td>";
        echo "<td>" . $um['material_type'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h3>🎯 Conclusão</h3>";
if (count($dashboardMaterials) > 0) {
    echo "✅ Sistema novo funcionando - " . count($dashboardMaterials) . " materiais encontrados<br>";
} elseif (count($legacyMaterials) > 0) {
    echo "⚠️ Usando sistema antigo - " . count($legacyMaterials) . " materiais encontrados<br>";
} else {
    echo "❌ Nenhum material encontrado em nenhum sistema<br>";
    echo "💡 Solução: Adicionar compras ou materiais para o usuário<br>";
}
?>
