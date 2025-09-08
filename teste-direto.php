<?php
// Teste DIRETO - sem rotas, sem sessão
require_once 'app/config.php';
require_once 'app/db.php';

$userId = 1; // Usar ID 1 diretamente
$db = Database::getInstance();

echo "<h1>🧪 Teste DIRETO - Sem Sessão</h1>";

// Query simples para buscar materiais
$materials = $db->fetchAll("
    SELECT DISTINCT m.*, up.purchase_date, up.item_type
    FROM user_purchases up
    LEFT JOIN product_material_mapping pmm ON up.hotmart_product_id = pmm.hotmart_product_id
    LEFT JOIN materials m ON pmm.material_id = m.id
    WHERE up.user_id = ? AND up.status = 'active' AND m.id IS NOT NULL
    ORDER BY up.purchase_date DESC
", [$userId]);

echo "<h2>Debug Info:</h2>";
echo "Usuário ID: $userId<br>";
echo "Total de materiais encontrados: " . count($materials) . "<br>";

if (empty($materials)) {
    echo "<h3>❌ Nenhum material encontrado</h3>";
    
    // Testar sistema antigo
    $legacyMaterials = $db->fetchAll("
        SELECT m.*, um.liberado_em as purchase_date, 'legacy' as item_type
        FROM user_materials um
        JOIN materials m ON um.material_id = m.id
        WHERE um.user_id = ?
        ORDER BY um.liberado_em DESC
    ", [$userId]);
    
    echo "Materiais no sistema antigo: " . count($legacyMaterials) . "<br>";
    
    if (!empty($legacyMaterials)) {
        echo "<h3>✅ Materiais encontrados no sistema antigo:</h3>";
        foreach ($legacyMaterials as $material) {
            echo "- " . $material['titulo'] . " (" . $material['tipo'] . ")<br>";
        }
    }
} else {
    echo "<h3>✅ Materiais encontrados no sistema novo:</h3>";
    foreach ($materials as $material) {
        echo "- " . $material['titulo'] . " (" . $material['tipo'] . ") - " . $material['item_type'] . "<br>";
    }
}

echo "<hr>";
echo "<p><a href='" . BASE_URL . "/teste-dashboard'>Teste com Rotas</a></p>";
echo "<p><a href='" . BASE_URL . "/debug-dashboard'>Debug Completo</a></p>";
?>
