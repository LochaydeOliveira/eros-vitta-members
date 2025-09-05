<?php
/**
 * Funções do Webhook - ValidaPro
 * Funções para processar webhooks da Hotmart
 */

// Função para gerar senha aleatória
function generateRandomPassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

// Função para processar dados da Hotmart
function processHotmartData($data) {
    // Log dos dados recebidos para debug
    error_log("Processando dados da Hotmart: " . json_encode($data));
    
    // Mapear campos da Hotmart para nosso sistema
    $processed = [
        'name' => '',
        'email' => '',
        'usuario' => 'Cliente',
        'plano' => 'Basic', // valor padrão
        'whatsapp' => '',
        'hotmart_data' => $data // Guardar dados originais
    ];
    
    // Extrair nome do comprador - múltiplas tentativas
    if (!empty($data['data']['buyer']['name'])) {
        $processed['name'] = trim($data['data']['buyer']['name']);
        error_log("Nome extraído via buyer.name: " . $processed['name']);
    } elseif (!empty($data['data']['buyer']['first_name']) && !empty($data['data']['buyer']['last_name'])) {
        $processed['name'] = trim($data['data']['buyer']['first_name'] . ' ' . $data['data']['buyer']['last_name']);
        error_log("Nome extraído via first_name + last_name: " . $processed['name']);
    } elseif (!empty($data['data']['buyer']['first_name'])) {
        $processed['name'] = trim($data['data']['buyer']['first_name']);
        error_log("Nome extraído via first_name: " . $processed['name']);
    } elseif (!empty($data['buyer']['name'])) {
        // Fallback para estrutura alternativa
        $processed['name'] = trim($data['buyer']['name']);
        error_log("Nome extraído via buyer.name (fallback): " . $processed['name']);
    }
    
    // Extrair email do comprador - múltiplas tentativas
    if (!empty($data['data']['buyer']['email'])) {
        $processed['email'] = trim($data['data']['buyer']['email']);
        error_log("Email extraído via buyer.email: " . $processed['email']);
    } elseif (!empty($data['buyer']['email'])) {
        // Fallback para estrutura alternativa
        $processed['email'] = trim($data['buyer']['email']);
        error_log("Email extraído via buyer.email (fallback): " . $processed['email']);
    }
    
    // Extrair WhatsApp se disponível
    if (!empty($data['data']['buyer']['phone'])) {
        $processed['whatsapp'] = trim($data['data']['buyer']['phone']);
        error_log("WhatsApp extraído via buyer.phone: " . $processed['whatsapp']);
    } elseif (!empty($data['buyer']['phone'])) {
        $processed['whatsapp'] = trim($data['buyer']['phone']);
        error_log("WhatsApp extraído via buyer.phone (fallback): " . $processed['whatsapp']);
    }
    
    // Determinar tipo de usuário baseado no produto
    if (!empty($data['data']['product']['name'])) {
        $product_name = strtolower($data['data']['product']['name']);
        error_log("Produto encontrado: " . $product_name);
        if (strpos($product_name, 'admin') !== false || strpos($product_name, 'administrador') !== false) {
            $processed['usuario'] = 'Administrador';
        } elseif (strpos($product_name, 'cliente') !== false || strpos($product_name, 'customer') !== false) {
            $processed['usuario'] = 'Cliente';
        }
        // Determinar plano pelo nome do produto
        if (strpos($product_name, 'pro') !== false) {
            $processed['plano'] = 'Pro';
        } elseif (strpos($product_name, 'anual') !== false) {
            $processed['plano'] = 'Anual';
        } elseif (strpos($product_name, 'basic') !== false) {
            $processed['plano'] = 'Basic';
        }
    } elseif (!empty($data['product']['name'])) {
        // Fallback para estrutura alternativa
        $product_name = strtolower($data['product']['name']);
        error_log("Produto encontrado (fallback): " . $product_name);
        if (strpos($product_name, 'admin') !== false || strpos($product_name, 'administrador') !== false) {
            $processed['usuario'] = 'Administrador';
        } elseif (strpos($product_name, 'cliente') !== false || strpos($product_name, 'customer') !== false) {
            $processed['usuario'] = 'Cliente';
        }
        // Determinar plano pelo nome do produto
        if (strpos($product_name, 'pro') !== false) {
            $processed['plano'] = 'Pro';
        } elseif (strpos($product_name, 'anual') !== false) {
            $processed['plano'] = 'Anual';
        } elseif (strpos($product_name, 'basic') !== false) {
            $processed['plano'] = 'Basic';
        }
    }
    
    // Log do resultado final
    error_log("Dados processados: " . json_encode($processed));
    
    return $processed;
}

// Função para criar usuário automaticamente
function createUserFromWebhook($data) {
    global $pdo;
    
    try {
        error_log("=== INICIANDO CRIAÇÃO DE USUÁRIO ===");
        error_log("Dados recebidos: " . json_encode($data));
        
        // Processar dados da Hotmart
        $processed_data = processHotmartData($data);
        error_log("Dados processados: " . json_encode($processed_data));
        
        // Validar dados obrigatórios
        if (empty($processed_data['email']) || empty($processed_data['name'])) {
            error_log("ERRO: Email ou nome vazios");
            error_log("Email: '" . $processed_data['email'] . "'");
            error_log("Nome: '" . $processed_data['name'] . "'");
            return [
                'success' => false, 
                'error' => 'Email e nome são obrigatórios',
                'received_data' => $data,
                'processed_data' => $processed_data
            ];
        }
        
        error_log("Dados válidos, verificando se usuário já existe...");
        
        // Verificar se usuário já existe
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$processed_data['email']]);
        if ($stmt->fetch()) {
            error_log("Usuário já existe: " . $processed_data['email']);
            return [
                'success' => false, 
                'error' => 'Usuário já existe',
                'email' => $processed_data['email']
            ];
        }
        
        error_log("Usuário não existe, gerando senha...");
        
        // Gerar senha aleatória
        $password = generateRandomPassword();
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        error_log("Senha gerada, inserindo usuário...");
        
        // Inserir usuário com plano e whatsapp
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, usuario, plano, whatsapp, active, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
        ");
        $stmt->execute([
            $processed_data['name'],
            $processed_data['email'],
            $hashed_password,
            $processed_data['usuario'],
            $processed_data['plano'],
            $processed_data['whatsapp']
        ]);
        
        $user_id = $pdo->lastInsertId();
        error_log("Usuário criado com ID: " . $user_id);
        
        // Enviar email de boas-vindas
        $email_sent = false;
        if (function_exists('sendWelcomeEmail')) {
            error_log("Enviando email de boas-vindas...");
            $email_sent = sendWelcomeEmail(
                $processed_data['email'], 
                $processed_data['name'], 
                $processed_data['email'], 
                $password
            );
            error_log("Email enviado: " . ($email_sent ? 'SIM' : 'NÃO'));
        } else {
            error_log("Função sendWelcomeEmail não encontrada");
        }
        
        // Log da criação
        error_log("Usuário criado via Hotmart webhook: {$processed_data['email']} - ID: {$user_id} - Produto: " . ($data['data']['product']['name'] ?? 'N/A'));
        
        return [
            'success' => true,
            'user_id' => $user_id,
            'email' => $processed_data['email'],
            'name' => $processed_data['name'],
            'password' => $password,
            'email_sent' => $email_sent,
            'hotmart_data' => $data
        ];
        
    } catch (Exception $e) {
        error_log("Erro ao criar usuário via Hotmart webhook: " . $e->getMessage());
        return [
            'success' => false, 
            'error' => 'Erro interno: ' . $e->getMessage(),
            'received_data' => $data
        ];
    }
}

// Função para verificar se IP está em range (para IPs da Hotmart)
function ip_in_range($ip, $range) {
    if (strpos($range, '/') === false) {
        return $ip === $range;
    }
    
    list($range, $netmask) = explode('/', $range, 2);
    $range_decimal = ip2long($range);
    $ip_decimal = ip2long($ip);
    $wildcard_decimal = pow(2, (32 - $netmask)) - 1;
    $netmask_decimal = ~ $wildcard_decimal;
    
    return (($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal));
} 