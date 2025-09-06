<?php
// Debug completo para erro 403
header('Content-Type: text/plain; charset=utf-8');

echo "=== DEBUG 403 - Eros Vitta ===\n";
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

echo "\n=== TESTE DE ESCRITA ===\n";
$test_file = __DIR__ . '/debug-test.txt';
if (file_put_contents($test_file, 'Teste de escrita - ' . date('Y-m-d H:i:s'))) {
    echo "✅ Escrita OK\n";
    unlink($test_file);
} else {
    echo "❌ Escrita FALHOU\n";
}

echo "\n=== TESTE DE LEITURA ===\n";
$read_file = __DIR__ . '/index.html';
if (file_exists($read_file)) {
    echo "✅ Leitura OK (index.html existe)\n";
} else {
    echo "❌ Leitura FALHOU (index.html não existe)\n";
}

echo "\n=== TESTE DE DIRETÓRIOS ===\n";
$dirs = ['area-membros', 'membros', 'libido-renovada', 'assets'];
foreach ($dirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (is_dir($path)) {
        echo "✅ $dir existe\n";
        if (is_readable($path)) {
            echo "  - Leitura: OK\n";
        } else {
            echo "  - Leitura: FALHOU\n";
        }
        if (is_writable($path)) {
            echo "  - Escrita: OK\n";
        } else {
            echo "  - Escrita: FALHOU\n";
        }
    } else {
        echo "❌ $dir não existe\n";
    }
}

echo "\n=== TESTE DE ARQUIVOS PHP ===\n";
$php_files = [
    'area-membros/test.php',
    'area-membros/status.php', 
    'membros/test.php',
    'libido-renovada/status.php'
];

foreach ($php_files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "✅ $file existe\n";
        if (is_readable($path)) {
            echo "  - Leitura: OK\n";
        } else {
            echo "  - Leitura: FALHOU\n";
        }
    } else {
        echo "❌ $file não existe\n";
    }
}

echo "\n=== TESTE DE .HTACCESS ===\n";
$htaccess_files = [
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
    } else {
        echo "❌ $file não existe\n";
    }
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

echo "\n=== TESTE DE ERRO LOG ===\n";
$error_log = ini_get('error_log');
if ($error_log) {
    echo "Error log configurado: $error_log\n";
    if (file_exists($error_log)) {
        echo "Arquivo de log existe\n";
        $log_content = file_get_contents($error_log);
        $recent_errors = array_slice(explode("\n", $log_content), -10);
        echo "Últimos 10 erros:\n";
        foreach ($recent_errors as $error) {
            if (trim($error)) {
                echo "  - $error\n";
            }
        }
    } else {
        echo "Arquivo de log não existe\n";
    }
} else {
    echo "Error log não configurado\n";
}

echo "\n=== TESTE DE MOD_REWRITE ===\n";
if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    if (in_array('mod_rewrite', $modules)) {
        echo "✅ mod_rewrite ativo\n";
    } else {
        echo "❌ mod_rewrite NÃO ativo\n";
    }
} else {
    echo "❌ Não é possível verificar módulos Apache\n";
}

echo "\n=== TESTE DE PERMISSÕES ===\n";
$test_perms = [
    'area-membros',
    'membros', 
    'libido-renovada'
];

foreach ($test_perms as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (is_dir($path)) {
        $perms = fileperms($path);
        echo "$dir: " . decoct($perms & 0777) . "\n";
    }
}

echo "\n=== FIM DO DEBUG ===\n";
?>
