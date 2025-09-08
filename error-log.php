<?php
// Sistema de logs de erro
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'error.log');

// Função para log personalizado
function logError($message, $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";
    
    if (!empty($context)) {
        $logMessage .= " | Context: " . json_encode($context);
    }
    
    error_log($logMessage);
    echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 5px 0; border-radius: 4px;'>";
    echo "<strong>ERRO:</strong> $message<br>";
    if (!empty($context)) {
        echo "<strong>Contexto:</strong> " . json_encode($context, JSON_PRETTY_PRINT);
    }
    echo "</div>";
}

// Função para log de sucesso
function logSuccess($message, $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";
    
    if (!empty($context)) {
        $logMessage .= " | Context: " . json_encode($context);
    }
    
    error_log($logMessage);
    echo "<div style='background: #d4edda; color: #155724; padding: 10px; margin: 5px 0; border-radius: 4px;'>";
    echo "<strong>SUCESSO:</strong> $message<br>";
    if (!empty($context)) {
        echo "<strong>Contexto:</strong> " . json_encode($context, JSON_PRETTY_PRINT);
    }
    echo "</div>";
}

// Função para log de informação
function logInfo($message, $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";
    
    if (!empty($context)) {
        $logMessage .= " | Context: " . json_encode($context);
    }
    
    error_log($logMessage);
    echo "<div style='background: #d1ecf1; color: #0c5460; padding: 10px; margin: 5px 0; border-radius: 4px;'>";
    echo "<strong>INFO:</strong> $message<br>";
    if (!empty($context)) {
        echo "<strong>Contexto:</strong> " . json_encode($context, JSON_PRETTY_PRINT);
    }
    echo "</div>";
}

echo "<h1>📋 Sistema de Logs Ativo</h1>";
echo "<p>Logs sendo gravados em: error.log</p>";
echo "<hr>";

// Testar o sistema de logs
logInfo("Sistema de logs iniciado");
logSuccess("Função de log funcionando corretamente");
logError("Este é um teste de erro (não é um erro real)");

echo "<hr>";
echo "<p><strong>Para ver os logs em tempo real, execute:</strong></p>";
echo "<code>tail -f error.log</code>";
?>
