<?php
require_once 'app/config.php';
require_once 'app/db.php';
require_once 'app/auth.php';
require_once 'app/mailer.php';
require_once 'app/accessControl.php';

// Verifica se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Método não permitido');
}

// Lê o corpo da requisição
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Verifica se o JSON é válido
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    die('JSON inválido');
}

// Valida se é um evento de compra aprovada
if (!isset($data['event']) || $data['event'] !== 'PURCHASE_APPROVED') {
    http_response_code(200);
    die('Evento não processado');
}

// Valida dados obrigatórios
$requiredFields = ['data', 'data.buyer', 'data.product'];
foreach ($requiredFields as $field) {
    $keys = explode('.', $field);
    $value = $data;
    foreach ($keys as $key) {
        if (!isset($value[$key])) {
            http_response_code(400);
            die("Campo obrigatório ausente: $field");
        }
        $value = $value[$key];
    }
}

$buyer = $data['data']['buyer'];
$product = $data['data']['product'];

// Valida email do comprador
if (!filter_var($buyer['email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    die('Email inválido');
}

$db = Database::getInstance();
$auth = new Auth();
$mailer = new Mailer();
$accessControl = new AccessControl();

try {
    $db->beginTransaction();
    
    // Verifica se o usuário já existe
    $existingUser = $db->fetch(
        "SELECT id FROM users WHERE email = ?",
        [$buyer['email']]
    );
    
    if ($existingUser) {
        $userId = $existingUser['id'];
        $user = $db->fetch(
            "SELECT * FROM users WHERE id = ?",
            [$userId]
        );
    } else {
        // Cria novo usuário
        $userData = $auth->createUser(
            $buyer['email'],
            $buyer['name'] ?? 'Cliente',
            null // Senha será gerada automaticamente
        );
        
        if (!$userData) {
            throw new Exception('Erro ao criar usuário');
        }
        
        $userId = $userData['id'];
        $user = $userData;
    }
    
    // Processar compra usando o novo sistema
    processPurchase($data, $userId, $accessControl);
    
    $db->commit();
    
    // Envia email de boas-vindas (apenas para novos usuários)
    if (!isset($existingUser)) {
        $mailer->sendWelcomeEmail(
            $user['email'],
            $user['nome'],
            $user['senha']
        );
    }
    
    // Resposta de sucesso
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Webhook processado com sucesso',
        'user_id' => $userId
    ]);
    
} catch (Exception $e) {
    $db->rollback();
    
    error_log("Erro no webhook: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro interno do servidor'
    ]);
}

/**
 * Processa a compra usando o novo sistema de mapeamento
 */
function processPurchase($data, $userId, $accessControl) {
    $purchase = $data['data'];
    $product = $purchase['product'];
    $transaction = $purchase['transaction'] ?? uniqid('TXN_');
    
    // Processar item principal
    processPurchasedItem($userId, $product, $transaction, 'main', $accessControl);
    
    // Processar Order Bump (se existir)
    if (isset($purchase['order_bump']) && $purchase['order_bump']['status'] === 'APPROVED') {
        processPurchasedItem($userId, $purchase['order_bump'], $transaction, 'order_bump', $accessControl);
    }
    
    // Processar Upsell (se existir)
    if (isset($purchase['upsell']) && $purchase['upsell']['status'] === 'APPROVED') {
        processUpsellPurchase($userId, $purchase['upsell'], $transaction, $accessControl);
    }
    
    // Processar Downsell (se existir)
    if (isset($purchase['downsell']) && $purchase['downsell']['status'] === 'APPROVED') {
        processPurchasedItem($userId, $purchase['downsell'], $transaction, 'downsell', $accessControl);
    }
}

/**
 * Processa um item comprado
 */
function processPurchasedItem($userId, $product, $transaction, $itemType, $accessControl) {
    // Buscar material correspondente
    $material = $accessControl->getMaterialByProductId($product['id']);
    
    if ($material) {
        // Adicionar compra do usuário
        $accessControl->addUserPurchase(
            $userId,
            $product['id'],
            $transaction,
            $itemType,
            $product['name'],
            $material['id']
        );
        
        // Log para debug
        error_log("Material liberado: {$material['titulo']} para usuário {$userId} (Tipo: {$itemType})");
    } else {
        // Log de erro se não encontrar mapeamento
        error_log("ERRO: Produto {$product['id']} não encontrado no mapeamento");
    }
}

/**
 * Processa compra do Pacote Premium
 */
function processUpsellPurchase($userId, $upsell, $transaction, $accessControl) {
    // Processar Pacote Premium (inclui múltiplos materiais)
    if ($upsell['id'] === 'A101789933P') {
        $accessControl->processUpsellPurchase($userId, $upsell['id'], $transaction, $upsell['name']);
        error_log("Pacote Premium processado para usuário {$userId}");
    } else {
        // Processar upsell normal
        processPurchasedItem($userId, $upsell, $transaction, 'upsell', $accessControl);
    }
}
?>
