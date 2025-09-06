<?php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/webhook_functions.php';

// Configurar headers
header('Content-Type: application/json; charset=utf-8');

// Verificar método HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

// Verificar autenticação via header
$webhook_secret = $_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? '';
if ($webhook_secret !== WEBHOOK_SHARED_SECRET) {
    http_response_code(401);
    echo json_encode(['error' => 'Token de autenticação inválido']);
    exit;
}

// Obter dados do webhook
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'JSON inválido']);
    exit;
}

// Log dos dados recebidos
app_log("Webhook recebido: " . $input);

try {
    // Verificar se é uma venda aprovada
    if (isset($data['event']) && $data['event'] === 'PURCHASE_APPROVED') {
        $buyer_data = $data['data']['buyer'] ?? [];
        $product_data = $data['data']['product'] ?? [];
        
        $email = $buyer_data['email'] ?? '';
        $name = $buyer_data['name'] ?? ($buyer_data['first_name'] . ' ' . $buyer_data['last_name'] ?? '');
        
        if (empty($email) || empty($name)) {
            throw new Exception('Email ou nome não encontrado nos dados do webhook');
        }
        
        // Verificar se usuário já existe
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $existing_user = $stmt->fetch();
        
        if ($existing_user) {
            // Usuário já existe, apenas registrar a compra
            $user_id = $existing_user['id'];
            app_log("Usuário já existe: $email - ID: $user_id");
        } else {
            // Criar novo usuário
            $result = createUserFromWebhook($data);
            if (!$result['success']) {
                throw new Exception('Erro ao criar usuário: ' . $result['error']);
            }
            $user_id = $result['user_id'];
            app_log("Usuário criado: $email - ID: $user_id");
        }
        
        // Registrar/atualizar compra
        $stmt = $pdo->prepare("
            INSERT INTO purchases (user_id, product_slug, status, approved_at, provider, provider_payload, created_at)
            VALUES (?, 'libido-renovado', 'approved', NOW(), 'hotmart', ?, NOW())
            ON DUPLICATE KEY UPDATE
            status = 'approved',
            approved_at = NOW(),
            provider_payload = VALUES(provider_payload)
        ");
        $stmt->execute([$user_id, json_encode($data)]);
        
        app_log("Compra registrada para usuário: $email - ID: $user_id");
        
        echo json_encode([
            'success' => true,
            'message' => 'Webhook processado com sucesso',
            'user_id' => $user_id,
            'email' => $email
        ]);
        
    } else {
        app_log("Evento não processado: " . ($data['event'] ?? 'desconhecido'));
        echo json_encode([
            'success' => true,
            'message' => 'Evento recebido mas não processado'
        ]);
    }
    
} catch (Exception $e) {
    app_log("Erro no webhook: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro interno do servidor',
        'message' => $e->getMessage()
    ]);
}
?>
