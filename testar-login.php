<?php
// Script para testar o login e gerar senha correta
require_once 'app/config.php';
require_once 'app/db.php';
require_once 'app/auth.php';

echo "<h2>🔍 Teste de Login - Eros Vitta</h2>";

// 1. Verificar conexão com banco
try {
    $db = Database::getInstance();
    echo "✅ Conexão com banco: OK<br>";
} catch (Exception $e) {
    echo "❌ Erro na conexão: " . $e->getMessage() . "<br>";
    exit;
}

// 2. Verificar se usuário existe
$user = $db->fetch("SELECT * FROM users WHERE email = ?", ['lochaydeguerreiro@hotmail.com']);

if ($user) {
    echo "✅ Usuário encontrado: " . $user['nome'] . "<br>";
    echo "📧 Email: " . $user['email'] . "<br>";
    echo "🔑 Hash da senha: " . $user['senha'] . "<br>";
} else {
    echo "❌ Usuário não encontrado!<br>";
    exit;
}

// 3. Testar senha
$senha_teste = '12345';
$senha_correta = password_verify($senha_teste, $user['senha']);

echo "<br><strong>🔐 Teste de Senha:</strong><br>";
echo "Senha testada: " . $senha_teste . "<br>";
echo "Resultado: " . ($senha_correta ? "✅ CORRETA" : "❌ INCORRETA") . "<br>";

// 4. Se senha estiver incorreta, gerar nova
if (!$senha_correta) {
    echo "<br><strong>🔧 Gerando nova senha:</strong><br>";
    $nova_senha_hash = password_hash($senha_teste, PASSWORD_DEFAULT);
    echo "Novo hash: " . $nova_senha_hash . "<br>";
    
    // Atualizar no banco
    $db->execute("UPDATE users SET senha = ? WHERE email = ?", [$nova_senha_hash, 'lochaydeguerreiro@hotmail.com']);
    echo "✅ Senha atualizada no banco!<br>";
}

// 5. Testar login completo
echo "<br><strong>🚀 Teste de Login Completo:</strong><br>";
$auth = new Auth();
$login_sucesso = $auth->login('lochaydeguerreiro@hotmail.com', '12345');

if ($login_sucesso) {
    echo "✅ Login realizado com sucesso!<br>";
    echo "👤 Usuário logado: " . $_SESSION['user_nome'] . "<br>";
    echo "🆔 ID da sessão: " . $_SESSION['user_id'] . "<br>";
} else {
    echo "❌ Falha no login!<br>";
}

// 6. Verificar materiais
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
}

echo "<br><strong>🎯 Dados para login:</strong><br>";
echo "URL: https://erosvitta.com.br/login<br>";
echo "Email: lochaydeguerreiro@hotmail.com<br>";
echo "Senha: 12345<br>";
?>
