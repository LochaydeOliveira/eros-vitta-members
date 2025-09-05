<?php
/**
 * Configurações do ValidaPro
 * Ambiente padrão de produção
 */

// Evita redefinir constantes caso o config seja incluído mais de uma vez
if (!defined('DB_TYPE')) {
    // Banco de Dados
    define('DB_TYPE', 'mysql');
    define('DB_HOST', 'localhost'); 
    define('DB_NAME', 'paymen58_validapro');
    define('DB_USER', 'paymen58');
    define('DB_PASS', 'u4q7+B6ly)obP_gxN9sNe');

    // Aplicação
    define('APP_NAME', 'ValidaPro');
    define('APP_URL', 'https://agencialed.com/validapro/');
    define('APP_VERSION', '2.0.0');

    // Email - Zoho
    define('SMTP_HOST', 'smtp.zoho.com');
    define('SMTP_PORT', 587);
    define('SMTP_USERNAME', 'contato@agencialed.com');
    define('SMTP_PASSWORD', 'Lochayde@154719');
    define('FROM_EMAIL', 'contato@agencialed.com');
    define('FROM_NAME', 'ValidaPro - Agência LED');

    // Segurança
    define('SESSION_TIMEOUT', 3600);
    define('PASSWORD_MIN_LENGTH', 8);
    define('MAX_LOGIN_ATTEMPTS', 5);
    define('LOGIN_TIMEOUT', 900);
    define('CSRF_TOKEN_TIMEOUT', 1800);
    define('SESSION_REGENERATION_TIME', 300);

    // Pontuação
    define('MAX_POINTS', 10);
    define('HIGH_POTENTIAL_MIN', 8);
    define('MEDIUM_POTENTIAL_MIN', 5);

    // Debug
    if (!defined('DEBUG_MODE')) {
        define('DEBUG_MODE', false);
    }
    if (!defined('SHOW_ERRORS')) {
        define('SHOW_ERRORS', false);
    }

    // Timezone e idioma
    date_default_timezone_set('America/Sao_Paulo');
    setlocale(LC_ALL, 'pt_BR.utf-8', 'pt_BR', 'Portuguese_Brazil');

    // Função para enviar headers de segurança (apenas se ainda não enviou nenhum output)
    function sendSecurityHeaders() {
    if (!headers_sent()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com; img-src 'self' data: https:; font-src 'self' https://cdnjs.cloudflare.com; connect-src 'self' https://cdnjs.cloudflare.com");
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        header('X-Permitted-Cross-Domain-Policies: none');
        }
    }

    // Função para pegar configuração (opcional)
    function getConfig($key, $default = null) {
        return defined($key) ? constant($key) : $default;
    }

    // Função de debug que só mostra se DEBUG_MODE ativo, e não gera output se headers já enviados
    function debug($message) {
        if (getConfig('DEBUG_MODE', false)) {
            if (!headers_sent()) {
                echo "<div style='background: #f0f0f0; border: 1px solid #ccc; padding: 10px; margin: 10px; font-family: monospace;'>";
                echo "<strong>DEBUG:</strong> " . htmlspecialchars($message);
                echo "</div>";
            } else {
                error_log("DEBUG: " . $message);
            }
        }
    }

    // Mensagem baseada na pontuação
    function getResultMessage($points) {
        $messages = [
            'high' => [
                'text' => 'Produto com alto potencial!',
                'icon' => 'fas fa-trophy',
                'color' => 'text-green-600',
                'bg_color' => 'bg-green-100'
            ],
            'medium' => [
                'text' => 'Produto razoável, com potencial',
                'icon' => 'fas fa-star',
                'color' => 'text-yellow-600',
                'bg_color' => 'bg-yellow-100'
            ],
            'low' => [
                'text' => 'Produto fraco, repense a escolha',
                'icon' => 'fas fa-exclamation-triangle',
                'color' => 'text-red-600',
                'bg_color' => 'bg-red-100'
            ]
        ];

        if ($points >= getConfig('HIGH_POTENTIAL_MIN')) {
            return $messages['high'];
        } elseif ($points >= getConfig('MEDIUM_POTENTIAL_MIN')) {
            return $messages['medium'];
        } else {
            return $messages['low'];
        }
    }
}

