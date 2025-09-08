<?php
// Debug do sistema de login
session_start();
require_once 'app/config.php';
require_once 'app/db.php';
require_once 'app/auth.php';

echo "<h2>🔍 Debug do Sistema de Login</h2>";

// 1. Verificar conexão com banco
try {
    $db = Database::getInstance();
    echo "✅ Conexão com banco: OK<br>";
} catch (Exception $e) {
    echo "❌ Erro na conexão: " . $e->getMessage() . "<br>";
    exit;
}

// 2. Verificar se usuário existe
$email = 'lochaydeguerreiro@hotmail.com';
$user = $db->fetch("SELECT * FROM users WHERE email = ?", [$email]);

if ($user) {
    echo "✅ Usuário encontrado:<br>";
    echo "ID: " . $user['id'] . "<br>";
    echo "Nome: " . $user['nome'] . "<br>";
    echo "Email: " . $user['email'] . "<br>";
    echo "Hash da senha: " . $user['senha'] . "<br>";
} else {
    echo "❌ Usuário não encontrado!<br>";
    echo "Execute o script resetar-usuarios.sql<br>";
    exit;
}

// 3. Testar senha
$senha_teste = '12345';
echo "<br><strong>🔐 Teste de Senha:</strong><br>";
echo "Senha testada: " . $senha_teste . "<br>";

$senha_correta = password_verify($senha_teste, $user['senha']);
echo "Resultado password_verify: " . ($senha_correta ? "✅ CORRETA" : "❌ INCORRETA") . "<br>";

// 4. Se senha estiver incorreta, gerar nova
if (!$senha_correta) {
    echo "<br><strong>🔧 Corrigindo senha:</strong><br>";
    $nova_senha_hash = password_hash($senha_teste, PASSWORD_DEFAULT);
    echo "Novo hash: " . $nova_senha_hash . "<br>";
    
    $db->execute("UPDATE users SET senha = ? WHERE email = ?", [$nova_senha_hash, $email]);
    echo "✅ Senha atualizada no banco!<br>";
    
    // Testar novamente
    $senha_correta = password_verify($senha_teste, $nova_senha_hash);
    echo "Teste após correção: " . ($senha_correta ? "✅ CORRETA" : "❌ INCORRETA") . "<br>";
}

// 5. Testar login com Auth
echo "<br><strong>🚀 Teste de Login com Auth:</strong><br>";
$auth = new Auth();
$login_sucesso = $auth->login($email, $senha_teste);

if ($login_sucesso) {
    echo "✅ Login realizado com sucesso!<br>";
    echo "👤 Sessão criada:<br>";
    echo "- user_id: " . ($_SESSION['user_id'] ?? 'NÃO DEFINIDO') . "<br>";
    echo "- user_email: " . ($_SESSION['user_email'] ?? 'NÃO DEFINIDO') . "<br>";
    echo "- user_nome: " . ($_SESSION['user_nome'] ?? 'NÃO DEFINIDO') . "<br>";
    echo "- login_time: " . ($_SESSION['login_time'] ?? 'NÃO DEFINIDO') . "<br>";
} else {
    echo "❌ Falha no login!<br>";
}

// 6. Verificar se está logado
echo "<br><strong>🔍 Verificação de Login:</strong><br>";
$is_logged = $auth->isLoggedIn();
echo "isLoggedIn(): " . ($is_logged ? "✅ SIM" : "❌ NÃO") . "<br>";

// 7. Testar getCurrentUser
$current_user = $auth->getCurrentUser();
if ($current_user) {
    echo "getCurrentUser(): ✅ " . $current_user['nome'] . "<br>";
} else {
    echo "getCurrentUser(): ❌ NULL<br>";
}

echo "<br><strong>🎯 Próximos passos:</strong><br>";
echo "1. Se tudo estiver ✅, tente fazer login em: <a href='https://erosvitta.com.br/login' target='_blank'>https://erosvitta.com.br/login</a><br>";
echo "2. Use: lochaydeguerreiro@hotmail.com / 12345<br>";
echo "3. Se ainda não funcionar, o problema pode estar no redirecionamento<br>";
?>
