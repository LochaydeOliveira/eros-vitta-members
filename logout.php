<?php
session_start();

// Registra o logout no log
if (isset($_SESSION['usuario'])) {
    error_log("Logout do usuário: " . $_SESSION['usuario']);
}

// Limpa todas as variáveis de sessão
$_SESSION = array();

// Destrói o cookie da sessão
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destrói a sessão
session_destroy();

// Redireciona para a página de login
header("Location: login.php");
exit;
?>