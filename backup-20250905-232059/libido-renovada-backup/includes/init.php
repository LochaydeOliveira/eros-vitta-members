<?php
/**
 * Inicialização Centralizada - ValidaPro
 * Gerencia carregamento de dependências e configurações
 */

// Iniciar buffer de saída para evitar "headers already sent"
ob_start();

// Carregar configurações
require_once __DIR__ . '/../config.php';

// Carregar conexão com banco
require_once __DIR__ . '/db.php';

// Carregar autenticação e sessões
require_once __DIR__ . '/auth.php';

// Carregar validações
require_once __DIR__ . '/validation.php';

// Carregar sistema de email
require_once __DIR__ . '/email.php';

// Carregar funções do webhook
require_once __DIR__ . '/webhook_functions.php';

// Carregar sugestões e templates
require_once __DIR__ . '/sugestoes.php';

// Função para finalizar inicialização
function finalizeInit() {
    // Inicializar sessão
    initSession();
    
    // Verificar timeout de sessão
    checkSessionTimeout();
    
    // Limpar sessões expiradas
    cleanupExpiredSessions();
}

// Função para enviar resposta e limpar buffer
function sendResponse() {
    $output = ob_get_contents();
    if ($output !== false) {
        ob_end_clean();
        echo $output;
    }
}

// Registrar função para executar no final
register_shutdown_function('sendResponse'); 