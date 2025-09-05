<?php

// Carrega as configurações de email
require_once __DIR__ . '/email.php';

// Configurações do Banco de Dados

define('DB_HOST', 'localhost');

define('DB_USER', 'paymen58');

define('DB_PASS', 'u4q7+B6ly)obP_gxN9sNe');

define('DB_NAME', 'paymen58_sistema_integrado_led');



// Configurações da Yampi

define('YAMPI_STORE_ALIAS', 'tutoriais-store');

define('YAMPI_TOKEN', 'ZhXMDywHOpG91oI0s4jPO8cuFy0chktKXGUL9XlQ');

define('YAMPI_WEBHOOK_SECRET', 'wh_rweQPzt0jQ5lRY3ZbrNYZQFFdjc8ZjDWOguYm');

define('YAMPI_PRODUCT_ID', '40621209');



// Configurações do Sistema

define('DOWNLOAD_EXPIRATION', 24); // horas

define('SITE_URL', 'https://agencialed.com');



// Configurações do Webhook

define('WEBHOOK_SECRET', YAMPI_WEBHOOK_SECRET);

define('WEBHOOK_URL', SITE_URL . '/webhook.php');



// Configuração do arquivo de log

define('LOG_FILE', __DIR__ . '/../logs/app.log');



// Cria o diretório de logs se não existir

if (!file_exists(dirname(LOG_FILE))) {

    mkdir(dirname(LOG_FILE), 0777, true);

} 