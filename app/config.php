<?php
// Configurações gerais da aplicação
define('DB_HOST', 'localhost');
define('DB_NAME', 'paymen58_eros_vitta');
define('DB_USER', 'paymen58');
define('DB_PASS', 'u4q7+B6ly)obP_gxN9sNe');
define('DB_CHARSET', 'utf8mb4');

// Configurações de email
define('SMTP_HOST', 'smtp.hostgator.com.br');
define('SMTP_PORT', 587);
define('SMTP_USER', 'contato@erosvitta.com.br');
define('SMTP_PASS', 'sua_senha_email');
define('FROM_EMAIL', 'contato@erosvitta.com.br');
define('FROM_NAME', 'ErosVitta');

// Configurações de segurança
define('SESSION_TIMEOUT', 3600); // 1 hora
define('SALT', 'erosvitta_salt_2025');

// URLs
define('BASE_URL', 'https://erosvitta.com.br');
define('LOGIN_URL', BASE_URL . '/login');
define('DASHBOARD_URL', BASE_URL . '/dashboard');

// Caminhos
define('ROOT_PATH', dirname(__DIR__));
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('VIEWS_PATH', ROOT_PATH . '/views');
define('ASSETS_PATH', ROOT_PATH . '/public/assets');

// Configurações de upload
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB
define('ALLOWED_EXTENSIONS', ['pdf', 'mp4', 'mp3', 'wav', 'html']);

// Timezone
date_default_timezone_set('America/Sao_Paulo');
?>
