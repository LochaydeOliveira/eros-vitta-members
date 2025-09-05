<?php
require_once __DIR__ . '/conexao.php';
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();
header('Location: login.php');
exit;

<?php
require_once 'includes/init.php';

// Finalizar inicialização
finalizeInit();

// Logout do usuário
if (isset($_SESSION['user_email'])) {
    error_log("Logout do usuário: " . $_SESSION['user_email']);
}

logout();

// Redirecionar para login
if (!headers_sent()) {
    header('Location: login.php');
    exit();
}
    echo '<script>window.location.href = "login.php";</script>';
    exit();
?> 