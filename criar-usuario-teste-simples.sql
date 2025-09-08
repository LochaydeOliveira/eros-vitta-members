-- Script SIMPLES para criar um usuário de teste
-- Execute este script no seu banco de dados

-- 1. Inserir usuário de teste (se não existir)
INSERT IGNORE INTO users (email, nome, senha) VALUES
('teste@exemplo.com', 'Usuário Teste', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- 2. Buscar o ID do usuário
SET @user_id = (SELECT id FROM users WHERE email = 'teste@exemplo.com');

-- 3. Liberar alguns materiais para o usuário de teste
INSERT IGNORE INTO user_materials (user_id, material_id) VALUES
(@user_id, 1), -- Guia Completo de Marketing Digital
(@user_id, 2), -- Curso de Vendas Online
(@user_id, 3), -- Meditação para Iniciantes
(@user_id, 4), -- Ebook de Receitas Saudáveis
(@user_id, 5); -- Tutorial de Fotografia

-- 4. Verificar se o usuário foi criado
SELECT 
    u.id,
    u.email,
    u.nome,
    u.criado_em,
    COUNT(um.id) as materiais_liberados
FROM users u
LEFT JOIN user_materials um ON u.id = um.user_id
WHERE u.email = 'teste@exemplo.com'
GROUP BY u.id;

-- 5. Verificar materiais liberados
SELECT 
    m.id,
    m.titulo,
    m.tipo,
    um.liberado_em
FROM user_materials um
JOIN materials m ON um.material_id = m.id
WHERE um.user_id = @user_id
ORDER BY um.liberado_em DESC;

-- DADOS DE LOGIN:
-- Email: teste@exemplo.com
-- Senha: password
