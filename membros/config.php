<?php
/**
 * Configuração do Sistema - Área de Membros
 * Configurações específicas para a área de membros
 */

// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'paymen58_db_libido');
define('DB_USER', 'paymen58');
define('DB_PASS', 'u4q7+B6ly)obP_gxN9sNe');

// Configurações de sessão
define('SESSION_TIMEOUT', 3600); // 1 hora
define('SESSION_REGENERATION_TIME', 300); // 5 minutos
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 900); // 15 minutos

// Configurações de segurança
define('WEBHOOK_SHARED_SECRET', 'JXw991Om7EB7yY0Xf8ptOB4FJMlQaP534873');

// Configurações de email (usar as do config principal)
require_once __DIR__ . '/../config/config.php';

// Configurações específicas da área de membros
define('MEMBER_AREA_NAME', 'Libido Renovado');
define('MEMBER_AREA_URL', 'https://erosvitta.com.br/membros/');
define('DOWNLOAD_DELAY_DAYS', 7); // Dias para liberar download após compra

// Função de log personalizada
function app_log($message) {
    $log_dir = __DIR__ . '/logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $log_file = $log_dir . '/app-' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message" . PHP_EOL;
    
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

// Função para enviar headers de segurança
function sendSecurityHeaders() {
    if (headers_sent()) return;
    
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}
