<?php
require_once __DIR__ . '/conexao.php';

// Fazer logout seguro e destruir sessão
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

// Redirecionar para login
if (!headers_sent()) {
    header('Location: login.php');
    exit();
}
echo '<script>window.location.href = "login.php";</script>';
exit();
?>