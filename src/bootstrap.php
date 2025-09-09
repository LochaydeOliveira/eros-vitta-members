<?php
declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    if (strpos($class, 'App\\') !== 0) {
        return;
    }
    $relative = str_replace('App\\', '', $class);
    $path = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require_once $path;
    }
});

date_default_timezone_set('America/Sao_Paulo');

if (!headers_sent()) {
    header('X-Powered-By: ErosVittaMembers');
}
