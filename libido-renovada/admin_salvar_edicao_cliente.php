<?php
require_once 'includes/init.php';
requireLogin();
require_once 'includes/db.php';
require_once 'includes/webhook_functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    $plano = trim($_POST['plano'] ?? 'Basic');
    $observacoes = trim($_POST['observacoes'] ?? '');
    $active = isset($_POST['active']) ? intval($_POST['active']) : 1;
    $reenviarAcesso = isset($_POST['reenviar_acesso']);

    if ($name && $email) {
        $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, whatsapp=?, plano=?, observacoes=?, active=? WHERE id=?");
        $stmt->execute([$name, $email, $whatsapp, $plano, $observacoes, $active, $id]);
        if ($reenviarAcesso) {
            $novaSenha = generateRandomPassword();
            $hash = password_hash($novaSenha, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->execute([$hash, $id]);
            if (function_exists('sendWelcomeEmail')) {
                sendWelcomeEmail($email, $name, $email, $novaSenha);
            }
        }
    }
}
header('Location: admin_clientes.php');
exit; 