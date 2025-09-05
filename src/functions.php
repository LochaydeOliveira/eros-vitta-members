<?php
/**
 * Função para registrar logs da aplicação
 * @param string $message Mensagem a ser registrada
 * @param string $level Nível do log (info, error, warning)
 */
function app_log($message) {
    try {
        $date = date('Y-m-d H:i:s');
        $logMessage = "[$date] $message" . PHP_EOL;
        file_put_contents(LOG_FILE, $logMessage, FILE_APPEND);
    } catch (Exception $e) {
        error_log("Erro ao escrever no log: " . $e->getMessage());
    }
}

// Função para gerar token único
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Função para validar email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Função para sanitizar string
function sanitizeString($str) {
    return htmlspecialchars(strip_tags(trim($str)));
} 