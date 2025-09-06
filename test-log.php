<?php
// Teste simples de log
error_log("TESTE DE LOG - " . date('Y-m-d H:i:s') . " - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A'));

echo "Log de teste enviado!\n";
echo "Data: " . date('Y-m-d H:i:s') . "\n";
echo "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A') . "\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "SAPI: " . php_sapi_name() . "\n";
echo "Error Log: " . ini_get('error_log') . "\n";
echo "Log Errors: " . (ini_get('log_errors') ? 'ON' : 'OFF') . "\n";
?>
