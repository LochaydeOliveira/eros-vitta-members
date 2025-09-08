<?php
// Teste simples de login
session_start();
require_once '../app/config.php';
require_once '../app/db.php';
require_once '../app/auth.php';

echo "<h2>🔐 Teste Simples de Login</h2>";

// 1. Verificar se usuário existe
$db = Database::getInstance();
$user = $db->fetch("SELECT * FROM users WHERE email = ?", ['lochaydeguerreiro@hotmail.com']);

if (!$user) {
    echo "❌ Usuário não encontrado!<br>";
    echo "Execute primeiro o script resetar-usuarios.sql<br>";
    exit;
}

echo "✅ Usuário encontrado: " . $user['nome'] . "<br>";
echo "📧 Email: " . $user['email'] . "<br>";

// 2. Gerar senha correta
$senha = '12345';
$senha_hash = password_hash($senha, PASSWORD_DEFAULT);

echo "<br><strong>🔑 Atualizando senha:</strong><br>";
echo "Senha: " . $senha . "<br>";
echo "Hash: " . $senha_hash . "<br>";

// Atualizar senha no banco
$db->execute("UPDATE users SET senha = ? WHERE email = ?", [$senha_hash, 'lochaydeguerreiro@hotmail.com']);
echo "✅ Senha atualizada!<br>";

// 3. Testar login
echo "<br><strong>🚀 Testando login:</strong><br>";
$auth = new Auth();
$login_sucesso = $auth->login('lochaydeguerreiro@hotmail.com', '12345');

if ($login_sucesso) {
    echo "✅ Login realizado com sucesso!<br>";
    echo "👤 Usuário: " . $_SESSION['user_nome'] . "<br>";
    echo "🆔 ID: " . $_SESSION['user_id'] . "<br>";
    echo "📧 Email: " . $_SESSION['user_email'] . "<br>";
    
    echo "<br><strong>🎯 Próximos passos:</strong><br>";
    echo "1. Acesse: <a href='https://erosvitta.com.br/dashboard' target='_blank'>https://erosvitta.com.br/dashboard</a><br>";
    echo "2. Ou faça login em: <a href='https://erosvitta.com.br/login' target='_blank'>https://erosvitta.com.br/login</a><br>";
} else {
    echo "❌ Falha no login!<br>";
    echo "Verifique se as sessões estão funcionando.<br>";
}

// 4. Verificar materiais
echo "<br><strong>📚 Materiais do usuário:</strong><br>";
$materiais = $db->fetchAll("
    SELECT m.titulo, m.tipo, um.liberado_em 
    FROM user_materials um 
    JOIN materials m ON um.material_id = m.id 
    WHERE um.user_id = ?
", [$user['id']]);

if ($materiais) {
    foreach ($materiais as $material) {
        echo "📖 " . $material['titulo'] . " (" . $material['tipo'] . ")<br>";
    }
} else {
    echo "❌ Nenhum material encontrado!<br>";
    echo "Execute o script resetar-usuarios.sql para liberar materiais.<br>";
}
?>
