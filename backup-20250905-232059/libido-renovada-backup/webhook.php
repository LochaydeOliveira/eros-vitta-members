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

// Configurações do webhook (usar segredo central da app)
$allowed_ips = [
    '127.0.0.1',
    '::1',
    // IPs da Hotmart (opcional, para segurança adicional)
    '52.67.0.0/16', // Hotmart AWS
    '54.232.0.0/16', // Hotmart AWS
    '54.233.0.0/16', // Hotmart AWS
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
        
        // Verificar autenticação via header X-Webhook-Secret
        $providedSecret = $_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? '';
        $expectedSecret = defined('WEBHOOK_SHARED_SECRET') ? (string)WEBHOOK_SHARED_SECRET : '';
        $auth_valid = ($providedSecret && $expectedSecret && hash_equals($expectedSecret, (string)$providedSecret));
        
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
            $payload = json_decode($input, true);
            if (!$payload) { $payload = $_POST; }

            if (empty($payload)) {
                error_log("Dados vazios recebidos");
                $response = ['success' => false, 'message' => 'Dados não fornecidos'];
            } else {
                error_log("Hotmart webhook recebido: " . json_encode($payload));

                // Determinar status e comprador
                $status = strtolower(trim($payload['status'] ?? $payload['purchase_status'] ?? $payload['event'] ?? ''));
                $buyerEmail = trim($payload['email'] ?? $payload['buyer_email'] ?? ($payload['buyer']['email'] ?? ''));
                $buyerName  = trim($payload['name']  ?? $payload['buyer_name'] ?? ($payload['buyer']['name'] ?? ''));
                $approvedAt = null;
                if (!empty($payload['approved_at'])) {
                    $approvedAt = date('Y-m-d H:i:s', strtotime($payload['approved_at']));
                } elseif (!empty($payload['purchase']['approved_date'])) {
                    $approvedAt = date('Y-m-d H:i:s', strtotime($payload['purchase']['approved_date']));
                } else {
                    $approvedAt = date('Y-m-d H:i:s');
                }

                $approvedStatuses = ['approved', 'paid', 'confirmed', 'completed'];
                if (!$buyerEmail || !in_array($status, $approvedStatuses, true)) {
                    $response = ['success' => true, 'ignored' => true];
                } else {
                    // Criar/ativar usuário e registrar purchase
                    require_once __DIR__ . '/conexao.php';
                    try {
                        $stmt = $pdo->prepare('SELECT id, name, email, active FROM users WHERE email = ? LIMIT 1');
                        $stmt->execute([$buyerEmail]);
                        $user = $stmt->fetch();

                        if ($user) {
                            if ((int)$user['active'] !== 1) {
                                $up = $pdo->prepare('UPDATE users SET active = 1 WHERE id = ?');
                                $up->execute([(int)$user['id']]);
                            }
                            $userId = (int)$user['id'];
                        } else {
                            $plainPass = function_exists('generateRandomPassword') ? generateRandomPassword(12) : (function(){ return bin2hex(random_bytes(6)); })();
                            $hash = password_hash($plainPass, PASSWORD_DEFAULT);
                            $nome = $buyerName ?: 'Cliente';
                            $ativo = 1;
                            $ins = $pdo->prepare('INSERT INTO users (name, email, password, usuario, active, created_at) VALUES (?, ?, ?, \'Cliente\', ?, NOW())');
                            $ins->execute([$nome, $buyerEmail, $hash, $ativo]);
                            $userId = (int)$pdo->lastInsertId();

                            // Email de boas-vindas
                            if (function_exists('send_app_email')) {
                                $loginUrl = rtrim(get_app_base_url(), '/') . '/login.php';
                                $html = '<p>Olá ' . htmlspecialchars($nome) . ',</p>'
                                      . '<p>Bem-vindo! Aqui estão seus dados de acesso ao e-book <strong>Libido Renovado</strong>:</p>'
                                      . '<p><b>Login:</b> ' . htmlspecialchars($buyerEmail) . '<br>'
                                      . '<b>Senha:</b> ' . htmlspecialchars($plainPass) . '</p>'
                                      . '<p>Acesse: <a href="' . htmlspecialchars($loginUrl) . '">' . htmlspecialchars($loginUrl) . '</a></p>'
                                      . '<p>Recomenda-se alterar a senha após o primeiro acesso.</p>';
                                @send_app_email($buyerEmail, 'Seus dados de acesso - Libido Renovado', $html);
                            }
                        }

                        // Registrar a compra aprovada
                        try {
                            $ins2 = $pdo->prepare('INSERT INTO purchases (user_id, status, approved_at, provider, provider_payload) VALUES (?, ?, ?, ?, ?)');
                            $ins2->execute([$userId, 'approved', $approvedAt, 'hotmart', json_encode($payload, JSON_UNESCAPED_UNICODE)]);
                        } catch (Throwable $e) {
                            error_log('Falha ao registrar purchase: ' . $e->getMessage());
                        }

                        $response = ['success' => true, 'user_id' => $userId];

                    } catch (Throwable $e) {
                        error_log('Erro no processamento do webhook: ' . $e->getMessage());
                        $response = ['success' => false, 'message' => 'Erro interno'];
                    }
                }
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