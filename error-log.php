<?php
// Sistema de logs para debug do erro 403
header('Content-Type: text/plain; charset=utf-8');

echo "=== ERROR LOG DEBUG ===\n";
echo "Data: " . date('Y-m-d H:i:s') . "\n";
echo "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A') . "\n";
echo "User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'N/A') . "\n";
echo "Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n";
echo "Request Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'N/A') . "\n";
echo "HTTPS: " . (isset($_SERVER['HTTPS']) ? 'SIM' : 'NÃO') . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n";
echo "Script Name: " . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "SAPI: " . php_sapi_name() . "\n";

echo "\n=== CONFIGURAÇÕES PHP ===\n";
echo "error_log: " . ini_get('error_log') . "\n";
echo "log_errors: " . (ini_get('log_errors') ? 'ON' : 'OFF') . "\n";
echo "display_errors: " . (ini_get('display_errors') ? 'ON' : 'OFF') . "\n";
echo "error_reporting: " . error_reporting() . "\n";

echo "\n=== TESTE DE ESCRITA DE LOG ===\n";
$log_file = __DIR__ . '/debug-403.log';
$log_message = "[" . date('Y-m-d H:i:s') . "] Teste de log - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A') . "\n";

if (file_put_contents($log_file, $log_message, FILE_APPEND | LOCK_EX)) {
    echo "✅ Log escrito com sucesso em: $log_file\n";
} else {
    echo "❌ Falha ao escrever log\n";
}

echo "\n=== TESTE DE PERMISSÕES ===\n";
$test_files = [
    'error-log.php',
    'test-simple.php',
    'test-simple.txt',
    'debug-403.php'
];

foreach ($test_files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        $perms = fileperms($path);
        $readable = is_readable($path) ? 'SIM' : 'NÃO';
        $writable = is_writable($path) ? 'SIM' : 'NÃO';
        echo "$file: perms=" . decoct($perms & 0777) . " readable=$readable writable=$writable\n";
    } else {
        echo "$file: NÃO EXISTE\n";
    }
}

echo "\n=== TESTE DE DIRETÓRIOS ===\n";
$test_dirs = [
    'area-membros',
    'membros',
    'libido-renovada',
    'assets'
];

foreach ($test_dirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (is_dir($path)) {
        $perms = fileperms($path);
        $readable = is_readable($path) ? 'SIM' : 'NÃO';
        $writable = is_writable($path) ? 'SIM' : 'NÃO';
        echo "$dir: perms=" . decoct($perms & 0777) . " readable=$readable writable=$writable\n";
    } else {
        echo "$dir: NÃO EXISTE\n";
    }
}

echo "\n=== TESTE DE .HTACCESS ===\n";
$htaccess_files = [
    '.htaccess',
    'area-membros/.htaccess',
    'membros/.htaccess',
    'libido-renovada/.htaccess'
];

foreach ($htaccess_files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "✅ $file existe\n";
        $content = file_get_contents($path);
        echo "  - Tamanho: " . strlen($content) . " bytes\n";
        if (strpos($content, 'AddHandler') !== false) {
            echo "  - AddHandler: ENCONTRADO\n";
        } else {
            echo "  - AddHandler: NÃO ENCONTRADO\n";
        }
        if (strpos($content, 'SetHandler') !== false) {
            echo "  - SetHandler: ENCONTRADO\n";
        } else {
            echo "  - SetHandler: NÃO ENCONTRADO\n";
        }
    } else {
        echo "❌ $file não existe\n";
    }
}

echo "\n=== TESTE DE MÓDULOS APACHE ===\n";
if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    $important_modules = ['mod_rewrite', 'mod_php', 'mod_mime'];
    foreach ($important_modules as $module) {
        if (in_array($module, $modules)) {
            echo "✅ $module ativo\n";
        } else {
            echo "❌ $module NÃO ativo\n";
        }
    }
} else {
    echo "❌ Não é possível verificar módulos Apache\n";
}

echo "\n=== TESTE DE HEADERS ===\n";
$headers = headers_list();
if (empty($headers)) {
    echo "Nenhum header enviado\n";
} else {
    foreach ($headers as $header) {
        echo "Header: $header\n";
    }
}

echo "\n=== TESTE DE ERRO LOG EXISTENTE ===\n";
$error_log_paths = [
    ini_get('error_log'),
    __DIR__ . '/php-errors.log',
    __DIR__ . '/debug-403.log',
    __DIR__ . '/error.log',
    '/var/log/apache2/error.log',
    '/var/log/httpd/error.log',
    '/var/log/nginx/error.log'
];

foreach ($error_log_paths as $log_path) {
    if ($log_path && file_exists($log_path)) {
        echo "✅ Log encontrado: $log_path\n";
        $log_content = file_get_contents($log_path);
        $recent_errors = array_slice(explode("\n", $log_content), -20);
        echo "Últimos 20 erros:\n";
        foreach ($recent_errors as $error) {
            if (trim($error) && strpos($error, '403') !== false) {
                echo "  - $error\n";
            }
        }
    } else {
        echo "❌ Log não encontrado: $log_path\n";
    }
}

echo "\n=== TESTE DE VARIÁVEIS DE AMBIENTE ===\n";
$env_vars = [
    'HTTP_HOST',
    'SERVER_NAME',
    'REQUEST_URI',
    'QUERY_STRING',
    'HTTP_REFERER',
    'HTTP_X_FORWARDED_FOR',
    'HTTP_X_REAL_IP',
    'REMOTE_ADDR',
    'SERVER_ADDR',
    'SERVER_PORT',
    'HTTPS',
    'REQUEST_METHOD',
    'CONTENT_TYPE',
    'CONTENT_LENGTH',
    'HTTP_USER_AGENT'
];

foreach ($env_vars as $var) {
    $value = $_SERVER[$var] ?? 'N/A';
    echo "$var: $value\n";
}

echo "\n=== TESTE DE FUNÇÕES PHP ===\n";
$php_functions = [
    'file_get_contents',
    'file_put_contents',
    'fopen',
    'fwrite',
    'fclose',
    'is_readable',
    'is_writable',
    'file_exists',
    'is_dir',
    'scandir',
    'opendir',
    'readdir',
    'closedir'
];

foreach ($php_functions as $func) {
    if (function_exists($func)) {
        echo "✅ $func disponível\n";
    } else {
        echo "❌ $func NÃO disponível\n";
    }
}

echo "\n=== FIM DO DEBUG ===\n";
?>
