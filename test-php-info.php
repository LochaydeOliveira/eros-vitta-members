<?php
echo "<h1>PHP Info - Eros Vitta</h1>";
echo "<p>Data: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>SAPI: " . php_sapi_name() . "</p>";
echo "<p>Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "</p>";
echo "<p>Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "</p>";
echo "<p>Script Name: " . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "</p>";
echo "<p>Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "</p>";
echo "<p>HTTPS: " . (isset($_SERVER['HTTPS']) ? 'SIM' : 'NÃO') . "</p>";

echo "<h2>Módulos Apache:</h2>";
if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    $important = ['mod_php', 'mod_rewrite', 'mod_mime'];
    foreach ($important as $mod) {
        echo "<p>" . ($modules && in_array($mod, $modules) ? "✅" : "❌") . " $mod</p>";
    }
} else {
    echo "<p>❌ Não é possível verificar módulos</p>";
}

echo "<h2>Configurações PHP:</h2>";
echo "<p>error_log: " . ini_get('error_log') . "</p>";
echo "<p>log_errors: " . (ini_get('log_errors') ? 'ON' : 'OFF') . "</p>";
echo "<p>display_errors: " . (ini_get('display_errors') ? 'ON' : 'OFF') . "</p>";
echo "<p>error_reporting: " . error_reporting() . "</p>";

echo "<h2>Teste de Escrita:</h2>";
$test_file = __DIR__ . '/test-write.txt';
if (file_put_contents($test_file, 'Teste de escrita - ' . date('Y-m-d H:i:s'))) {
    echo "<p>✅ Escrita OK</p>";
    unlink($test_file);
} else {
    echo "<p>❌ Escrita FALHOU</p>";
}

echo "<h2>Teste de .htaccess:</h2>";
$htaccess = __DIR__ . '/.htaccess';
if (file_exists($htaccess)) {
    echo "<p>✅ .htaccess existe</p>";
    $content = file_get_contents($htaccess);
    echo "<p>Tamanho: " . strlen($content) . " bytes</p>";
    if (strpos($content, 'AddHandler') !== false) {
        echo "<p>✅ AddHandler encontrado</p>";
    } else {
        echo "<p>❌ AddHandler NÃO encontrado</p>";
    }
} else {
    echo "<p>❌ .htaccess não existe</p>";
}

echo "<h2>Permissões:</h2>";
$perms = fileperms(__DIR__);
echo "<p>Diretório atual: " . decoct($perms & 0777) . "</p>";

echo "<h2>Headers:</h2>";
$headers = headers_list();
if (empty($headers)) {
    echo "<p>Nenhum header enviado</p>";
} else {
    foreach ($headers as $header) {
        echo "<p>Header: $header</p>";
    }
}

echo "<h2>Variáveis de Ambiente:</h2>";
echo "<p>HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'N/A') . "</p>";
echo "<p>SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'N/A') . "</p>";
echo "<p>REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "</p>";
echo "<p>QUERY_STRING: " . ($_SERVER['QUERY_STRING'] ?? 'N/A') . "</p>";
echo "<p>HTTP_REFERER: " . ($_SERVER['HTTP_REFERER'] ?? 'N/A') . "</p>";
echo "<p>REMOTE_ADDR: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A') . "</p>";
echo "<p>HTTP_USER_AGENT: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'N/A') . "</p>";

echo "<h2>Teste de Funções:</h2>";
$functions = ['file_get_contents', 'file_put_contents', 'fopen', 'fwrite', 'fclose'];
foreach ($functions as $func) {
    echo "<p>" . (function_exists($func) ? "✅" : "❌") . " $func</p>";
}

echo "<h2>Teste de Erro Log:</h2>";
$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log)) {
    echo "<p>✅ Error log existe: $error_log</p>";
    $log_content = file_get_contents($error_log);
    $recent_errors = array_slice(explode("\n", $log_content), -10);
    echo "<p>Últimos 10 erros:</p>";
    echo "<pre>";
    foreach ($recent_errors as $error) {
        if (trim($error)) {
            echo htmlspecialchars($error) . "\n";
        }
    }
    echo "</pre>";
} else {
    echo "<p>❌ Error log não encontrado: $error_log</p>";
}

echo "<h2>Teste de Diretórios:</h2>";
$dirs = ['area-membros', 'membros', 'libido-renovada'];
foreach ($dirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (is_dir($path)) {
        $perms = fileperms($path);
        $readable = is_readable($path) ? 'SIM' : 'NÃO';
        $writable = is_writable($path) ? 'SIM' : 'NÃO';
        echo "<p>✅ $dir existe - perms: " . decoct($perms & 0777) . " - readable: $readable - writable: $writable</p>";
    } else {
        echo "<p>❌ $dir não existe</p>";
    }
}

echo "<h2>Teste de Arquivos PHP:</h2>";
$php_files = [
    'test-php-simple.php',
    'test-php-info.php',
    'area-membros/test.php',
    'area-membros/status.php',
    'membros/test.php',
    'libido-renovada/status.php'
];

foreach ($php_files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        $perms = fileperms($path);
        $readable = is_readable($path) ? 'SIM' : 'NÃO';
        echo "<p>✅ $file existe - perms: " . decoct($perms & 0777) . " - readable: $readable</p>";
    } else {
        echo "<p>❌ $file não existe</p>";
    }
}

echo "<h2>Teste de .htaccess por Pasta:</h2>";
$htaccess_files = [
    '.htaccess',
    'area-membros/.htaccess',
    'membros/.htaccess',
    'libido-renovada/.htaccess'
];

foreach ($htaccess_files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "<p>✅ $file existe</p>";
        $content = file_get_contents($path);
        echo "<p>Tamanho: " . strlen($content) . " bytes</p>";
        if (strpos($content, 'AddHandler') !== false) {
            echo "<p>✅ AddHandler encontrado</p>";
        } else {
            echo "<p>❌ AddHandler NÃO encontrado</p>";
        }
        if (strpos($content, 'SetHandler') !== false) {
            echo "<p>✅ SetHandler encontrado</p>";
        } else {
            echo "<p>❌ SetHandler NÃO encontrado</p>";
        }
    } else {
        echo "<p>❌ $file não existe</p>";
    }
}

echo "<h2>Teste de Módulos Apache:</h2>";
if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    $important_modules = ['mod_php', 'mod_rewrite', 'mod_mime', 'mod_headers', 'mod_dir'];
    foreach ($important_modules as $module) {
        if (in_array($module, $modules)) {
            echo "<p>✅ $module ativo</p>";
        } else {
            echo "<p>❌ $module NÃO ativo</p>";
        }
    }
} else {
    echo "<p>❌ Não é possível verificar módulos Apache</p>";
}

echo "<h2>Teste de Headers:</h2>";
$headers = headers_list();
if (empty($headers)) {
    echo "<p>Nenhum header enviado</p>";
} else {
    foreach ($headers as $header) {
        echo "<p>Header: $header</p>";
    }
}

echo "<h2>Teste de Variáveis de Ambiente:</h2>";
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
    echo "<p>$var: $value</p>";
}

echo "<h2>Teste de Funções PHP:</h2>";
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
        echo "<p>✅ $func disponível</p>";
    } else {
        echo "<p>❌ $func NÃO disponível</p>";
    }
}

echo "<h2>Teste de Erro Log:</h2>";
$error_log = ini_get('error_log');
if ($error_log) {
    echo "<p>Error log configurado: $error_log</p>";
    if (file_exists($error_log)) {
        echo "<p>Arquivo de log existe</p>";
        $log_content = file_get_contents($error_log);
        $recent_errors = array_slice(explode("\n", $log_content), -20);
        echo "<p>Últimos 20 erros:</p>";
        echo "<pre>";
        foreach ($recent_errors as $error) {
            if (trim($error)) {
                echo htmlspecialchars($error) . "\n";
            }
        }
        echo "</pre>";
    } else {
        echo "<p>Arquivo de log não existe</p>";
    }
} else {
    echo "<p>Error log não configurado</p>";
}

echo "<h2>Teste de Permissões:</h2>";
$test_perms = [
    'area-membros',
    'membros', 
    'libido-renovada'
];

foreach ($test_perms as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (is_dir($path)) {
        $perms = fileperms($path);
        echo "<p>$dir: " . decoct($perms & 0777) . "</p>";
    }
}

echo "<h2>FIM DO DEBUG</h2>";
?>
