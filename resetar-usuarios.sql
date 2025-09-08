-- Script para deletar todos os usuários e criar um novo
-- Execute este script no seu banco de dados

-- 1. Deletar todas as relações de materiais dos usuários
DELETE FROM user_materials;

-- 2. Deletar todas as compras dos usuários (se a tabela existir)
DELETE FROM user_purchases;

-- 3. Deletar todos os usuários
DELETE FROM users;

-- 4. Resetar o auto_increment da tabela users
ALTER TABLE users AUTO_INCREMENT = 1;

-- 5. Criar novo usuário com os dados especificados
INSERT INTO users (email, nome, senha) VALUES
('lochaydeguerreiro@hotmail.com', 'Lochayde Guerreiro', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- 6. Buscar o ID do novo usuário
SET @user_id = LAST_INSERT_ID();

-- 7. Liberar alguns materiais para o novo usuário
INSERT INTO user_materials (user_id, material_id) VALUES
(@user_id, 1), -- Guia Completo de Marketing Digital
(@user_id, 2), -- Curso de Vendas Online
(@user_id, 3), -- Meditação para Iniciantes
(@user_id, 4), -- Ebook de Receitas Saudáveis
(@user_id, 5); -- Tutorial de Fotografia

-- 8. Verificar se o usuário foi criado corretamente
SELECT 
    u.id,
    u.email,
    u.nome,
    u.criado_em,
    COUNT(um.id) as materiais_liberados
FROM users u
LEFT JOIN user_materials um ON u.id = um.user_id
WHERE u.email = 'lochaydeguerreiro@hotmail.com'
GROUP BY u.id;

-- 9. Verificar materiais liberados
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
-- Email: lochaydeguerreiro@hotmail.com
-- Senha: 12345
