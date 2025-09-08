<?php
// Teste direto da dashboard
session_start();
require_once 'app/config.php';
require_once 'app/db.php';
require_once 'app/auth.php';

// Verificar se usuário existe e corrigir senha
$db = Database::getInstance();
$user = $db->fetch("SELECT * FROM users WHERE email = ?", ['lochaydeguerreiro@hotmail.com']);

if ($user) {
    // Corrigir senha
    $senha_hash = password_hash('12345', PASSWORD_DEFAULT);
    $db->execute("UPDATE users SET senha = ? WHERE email = ?", [$senha_hash, 'lochaydeguerreiro@hotmail.com']);
    
    // Fazer login
    $auth = new Auth();
    $auth->login('lochaydeguerreiro@hotmail.com', '12345');
    
    echo "<h1>✅ Login realizado com sucesso!</h1>";
    echo "<p>Usuário: " . $_SESSION['user_nome'] . "</p>";
    echo "<p><a href='dashboard'>Ir para Dashboard</a></p>";
} else {
    echo "<h1>❌ Usuário não encontrado!</h1>";
    echo "<p>Execute o script resetar-usuarios.sql primeiro</p>";
}
?>
