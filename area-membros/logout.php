<?php
require_once 'config.php';
require_once 'includes/auth.php';

// Fazer logout
logout();

// Redirecionar para login
header('Location: login.php?success=logout');
exit();
?>
