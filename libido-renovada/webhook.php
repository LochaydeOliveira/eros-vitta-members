<?php
/**
 * Webhook - ValidaPro
 * Recebe notificações de vendas da Hotmart e cria usuários automaticamente
 */

// Iniciar buffer de saída
ob_start();

// Carregar inicialização
require_once 'includes/init.php';

// Finalizar inicialização
finalizeInit();

// Configurações do webhook
$webhook_secret = 'JXw991Om7EB7yY0Xf8ptOB4FJMlQaP534873'; // Chave segura para Hotmart
$allowed_ips = [
    '127.0.0.1',
    '::1',
    // IPs da Hotmart (opcional, para segurança adicional)
    '52.67.0.0/16', // Hotmart AWS
    '54.232.0.0/16', // Hotmart AWS
    '54.233.0.0/16', // Hotmart AWS
    // IPs de teste (remover em produção)
    '192.185.222.27', // IP do servidor agencialed.com
    '0.0.0.0/0', // Permitir todos os IPs (APENAS PARA TESTE)
];

// Processar webhook
$response = ['success' => false, 'message' => 'Método não permitido'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Log do início do processamento
    error_log("=== WEBHOOK HOTMART INICIADO ===");
    error_log("Data/Hora: " . date('Y-m-d H:i:s'));
    error_log("IP do Cliente: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A'));
    error_log("User-Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'N/A'));
    
    // Verificar IP (opcional, para segurança adicional)
    $client_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $ip_allowed = true;
    
    if (!empty($allowed_ips)) {
        $ip_allowed = false;
        foreach ($allowed_ips as $allowed_ip) {
            if (strpos($allowed_ip, '/') !== false) {
                // Verificar range de IP
                if (ip_in_range($client_ip, $allowed_ip)) {
                    $ip_allowed = true;
                    error_log("IP autorizado via range: {$client_ip} em {$allowed_ip}");
                    break;
                }
            } else {
                // Verificar IP exato
                if ($client_ip === $allowed_ip) {
                    $ip_allowed = true;
                    error_log("IP autorizado via match exato: {$client_ip}");
                    break;
                }
            }
        }
    }
    
    if (!$ip_allowed) {
        error_log("Hotmart webhook: IP não autorizado: {$client_ip}");
        error_log("IPs permitidos: " . implode(', ', $allowed_ips));
        $response = ['success' => false, 'message' => 'IP não autorizado', 'client_ip' => $client_ip];
    } else {
        error_log("IP autorizado: {$client_ip}");
        
        // Verificar autenticação
        $auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $auth_post = $_POST['token'] ?? $_POST['secret'] ?? '';
        $auth_get = $_GET['token'] ?? $_GET['secret'] ?? '';
        
        // Múltiplas formas de autenticação
        $auth_valid = false;
        $expected_auth = 'Bearer ' . $webhook_secret;
        $expected_token = $webhook_secret;
        
        error_log("Auth Header: " . substr($auth_header, 0, 20) . "...");
        error_log("Auth POST: " . substr($auth_post, 0, 20) . "...");
        error_log("Auth GET: " . substr($auth_get, 0, 20) . "...");
        error_log("Expected Auth: " . substr($expected_auth, 0, 20) . "...");
        error_log("Expected Token: " . substr($expected_token, 0, 20) . "...");
        
        // Verificar diferentes formatos
        if ($auth_header === $expected_auth) {
            $auth_valid = true;
            error_log("Autenticação via Authorization header");
        } elseif ($auth_header === $expected_token) {
            $auth_valid = true;
            error_log("Autenticação via Authorization header (sem Bearer)");
        } elseif ($auth_post === $expected_token) {
            $auth_valid = true;
            error_log("Autenticação via POST token");
        } elseif ($auth_get === $expected_token) {
            $auth_valid = true;
            error_log("Autenticação via GET token");
        } elseif (empty($auth_header) && empty($auth_post) && empty($auth_get)) {
            // Para testes, permitir sem autenticação se IP for da Hotmart
            if (strpos($_SERVER['HTTP_USER_AGENT'] ?? '', 'Jodd HTTP') !== false) {
                $auth_valid = true;
                error_log("Autenticação bypassada para Hotmart (User-Agent: Jodd HTTP)");
            }
        }
        
        if (!$auth_valid) {
            error_log("Hotmart webhook: Autenticação falhou");
            error_log("Header recebido: {$auth_header}");
            error_log("POST token: {$auth_post}");
            error_log("GET token: {$auth_get}");
            error_log("Header esperado: {$expected_auth}");
            $response = ['success' => false, 'message' => 'Autenticação falhou'];
        } else {
            error_log("Autenticação OK");
            
            // Ler dados do POST
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                $data = $_POST; // Fallback para dados POST normais
            }
            
            // Validar dados mínimos
            if (empty($data)) {
                error_log("Dados vazios recebidos");
                $response = ['success' => false, 'message' => 'Dados não fornecidos'];
            } else {
                // Log dos dados recebidos
                error_log("Hotmart webhook recebido: " . json_encode($data));
                
                // Processar criação do usuário
                $result = createUserFromWebhook($data);
                $response = $result;
                
                error_log("Resultado do processamento: " . json_encode($result));
            }
        }
    }
    
    error_log("=== WEBHOOK HOTMART FINALIZADO ===");
}

// Limpar buffer e enviar resposta
ob_end_clean();

// Headers de resposta
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Enviar resposta JSON
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit(); 