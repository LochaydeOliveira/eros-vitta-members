<?php
// Script para simular uma compra da Hotmart e testar o webhook
require_once 'app/config.php';
require_once 'app/db.php';
require_once 'app/auth.php';
require_once 'app/mailer.php';
require_once 'app/accessControl.php';

echo "<h2>🛒 Simulador de Compra - Eros Vitta</h2>";

// 1. Verificar conexão
try {
    $db = Database::getInstance();
    $auth = new Auth();
    $accessControl = new AccessControl();
    echo "✅ Conexão com banco: OK<br>";
} catch (Exception $e) {
    echo "❌ Erro na conexão: " . $e->getMessage() . "<br>";
    exit;
}

// 2. Dados da compra simulada
$compra_simulada = [
    'event' => 'PURCHASE_APPROVED',
    'data' => [
        'buyer' => [
            'email' => 'lochaydeguerreiro@hotmail.com',
            'name' => 'Lochayde Guerreiro'
        ],
        'product' => [
            'id' => 'E101649402I',
            'name' => 'Libido Renovada: O Plano de 21 Dias para Casais'
        ],
        'transaction' => 'TXN_SIMULADA_' . time(),
        'order_bump' => [
            'id' => 'F101670521N',
            'name' => 'O Guia Rápido dos 5 Toques Mágicos',
            'status' => 'APPROVED'
        ],
        'upsell' => [
            'id' => 'A101789933P',
            'name' => 'Pacote PREMIUM - Libido Renovado',
            'status' => 'APPROVED'
        ]
    ]
];

echo "<br><strong>🛒 Simulando compra:</strong><br>";
echo "📧 Cliente: " . $compra_simulada['data']['buyer']['email'] . "<br>";
echo "🛍️ Produto: " . $compra_simulada['data']['product']['name'] . "<br>";
echo "🎁 Order Bump: " . $compra_simulada['data']['order_bump']['name'] . "<br>";
echo "💎 Upsell: " . $compra_simulada['data']['upsell']['name'] . "<br>";

// 3. Processar webhook
echo "<br><strong>🔄 Processando webhook:</strong><br>";

try {
    $db->beginTransaction();
    
    // Verificar se usuário existe
    $existingUser = $db->fetch(
        "SELECT id FROM users WHERE email = ?",
        [$compra_simulada['data']['buyer']['email']]
    );
    
    if ($existingUser) {
        $userId = $existingUser['id'];
        $user = $db->fetch("SELECT * FROM users WHERE id = ?", [$userId]);
        echo "✅ Usuário existente encontrado: " . $user['nome'] . "<br>";
    } else {
        // Criar novo usuário
        $userData = $auth->createUser(
            $compra_simulada['data']['buyer']['email'],
            $compra_simulada['data']['buyer']['name'],
            '12345' // Senha padrão
        );
        
        if (!$userData) {
            throw new Exception('Erro ao criar usuário');
        }
        
        $userId = $userData['id'];
        $user = $userData;
        echo "✅ Novo usuário criado: " . $user['nome'] . "<br>";
    }
    
    // Processar compra usando o sistema de mapeamento
    processPurchase($compra_simulada, $userId, $accessControl);
    
    $db->commit();
    echo "✅ Webhook processado com sucesso!<br>";
    
} catch (Exception $e) {
    $db->rollback();
    echo "❌ Erro no webhook: " . $e->getMessage() . "<br>";
    exit;
}

// 4. Verificar materiais liberados
echo "<br><strong>📚 Materiais liberados:</strong><br>";
$materiais = $accessControl->getUserPurchasedMaterials($userId);

if ($materiais) {
    foreach ($materiais as $material) {
        echo "📖 " . $material['titulo'] . " (" . $material['tipo'] . ") - " . $material['item_type'] . "<br>";
    }
} else {
    echo "❌ Nenhum material encontrado!<br>";
}

// 5. Testar login
echo "<br><strong>🔐 Testando login:</strong><br>";
$login_sucesso = $auth->login('lochaydeguerreiro@hotmail.com', '12345');

if ($login_sucesso) {
    echo "✅ Login realizado com sucesso!<br>";
    echo "👤 Usuário logado: " . $_SESSION['user_nome'] . "<br>";
    echo "🆔 ID da sessão: " . $_SESSION['user_id'] . "<br>";
} else {
    echo "❌ Falha no login!<br>";
}

echo "<br><strong>🎯 Dados para acesso:</strong><br>";
echo "URL: https://erosvitta.com.br/login<br>";
echo "Email: lochaydeguerreiro@hotmail.com<br>";
echo "Senha: 12345<br>";
echo "Dashboard: https://erosvitta.com.br/dashboard<br>";

// Funções do webhook
function processPurchase($data, $userId, $accessControl) {
    $purchase = $data['data'];
    $product = $purchase['product'];
    $transaction = $purchase['transaction'];
    
    // Processar item principal
    processPurchasedItem($userId, $product, $transaction, 'main', $accessControl);
    
    // Processar Order Bump
    if (isset($purchase['order_bump']) && $purchase['order_bump']['status'] === 'APPROVED') {
        processPurchasedItem($userId, $purchase['order_bump'], $transaction, 'order_bump', $accessControl);
    }
    
    // Processar Upsell
    if (isset($purchase['upsell']) && $purchase['upsell']['status'] === 'APPROVED') {
        processUpsellPurchase($userId, $purchase['upsell'], $transaction, $accessControl);
    }
}

function processPurchasedItem($userId, $product, $transaction, $itemType, $accessControl) {
    $material = $accessControl->getMaterialByProductId($product['id']);
    
    if ($material) {
        $accessControl->addUserPurchase(
            $userId,
            $product['id'],
            $transaction,
            $itemType,
            $product['name'],
            $material['id']
        );
        echo "✅ Material liberado: {$material['titulo']} ({$itemType})<br>";
    } else {
        echo "⚠️ Produto {$product['id']} não encontrado no mapeamento<br>";
    }
}

function processUpsellPurchase($userId, $upsell, $transaction, $accessControl) {
    if ($upsell['id'] === 'A101789933P') {
        $accessControl->processUpsellPurchase($userId, $upsell['id'], $transaction, $upsell['name']);
        echo "✅ Pacote Premium processado<br>";
    } else {
        processPurchasedItem($userId, $upsell, $transaction, 'upsell', $accessControl);
    }
}
?>
