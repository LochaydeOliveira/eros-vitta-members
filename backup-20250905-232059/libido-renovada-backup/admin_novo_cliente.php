<?php
require_once 'includes/init.php';
requireLogin();
require_once 'includes/db.php';
require_once 'includes/webhook_functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    $plano = trim($_POST['plano'] ?? 'Basic');
    $observacoes = trim($_POST['observacoes'] ?? '');
    $enviarAcesso = isset($_POST['enviar_acesso']);

    if ($name && $email) {
        $senha = generateRandomPassword();
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, usuario, plano, whatsapp, observacoes, active, created_at) VALUES (?, ?, ?, 'Cliente', ?, ?, ?, 1, NOW())");
        $stmt->execute([$name, $email, $hash, $plano, $whatsapp, $observacoes]);
        if ($enviarAcesso && function_exists('sendWelcomeEmail')) {
            sendWelcomeEmail($email, $name, $email, $senha);
        }
    }
}
header('Location: admin_clientes.php');
exit; 