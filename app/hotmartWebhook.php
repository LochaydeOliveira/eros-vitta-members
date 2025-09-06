<?php
require_once 'config.php';
require_once 'db.php';
require_once 'auth.php';
require_once 'mailer.php';

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
    
    // Processa os materiais do produto
    $materials = $this->getProductMaterials($product['id']);
    
    foreach ($materials as $material) {
        // Verifica se o material já foi liberado para o usuário
        $existingMaterial = $db->fetch(
            "SELECT id FROM user_materials WHERE user_id = ? AND material_id = ?",
            [$userId, $material['id']]
        );
        
        if (!$existingMaterial) {
            // Libera o material para o usuário
            $auth->addUserMaterial($userId, $material['id']);
        }
    }
    
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
 * Busca os materiais associados a um produto
 * Em uma implementação real, isso viria de uma API ou banco de dados
 */
function getProductMaterials($productId) {
    $db = Database::getInstance();
    
    // Mapeamento de produtos para materiais (exemplo)
    $productMaterials = [
        '12345' => [1, 2, 3], // Produto 12345 tem materiais 1, 2 e 3
        '67890' => [4, 5],    // Produto 67890 tem materiais 4 e 5
    ];
    
    $materialIds = $productMaterials[$productId] ?? [];
    
    if (empty($materialIds)) {
        return [];
    }
    
    $placeholders = str_repeat('?,', count($materialIds) - 1) . '?';
    
    return $db->fetchAll(
        "SELECT * FROM materials WHERE id IN ($placeholders)",
        $materialIds
    );
}
?>
