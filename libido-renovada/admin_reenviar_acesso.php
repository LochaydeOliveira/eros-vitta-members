<?php
require_once 'includes/init.php';
requireLogin();
require_once 'includes/db.php';
require_once 'includes/webhook_functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $novaSenha = generateRandomPassword();
        $hash = password_hash($novaSenha, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hash, $id]);
        if (function_exists('sendWelcomeEmail')) {
            sendWelcomeEmail($user['email'], $user['name'], $user['email'], $novaSenha);
        }
    }
}
header('Location: admin_clientes.php');
exit; 