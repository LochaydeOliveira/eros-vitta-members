-- Script para simular compra do Pacote Premium
-- Execute este script no seu banco de dados

-- 1. Verificar se o usuário existe
SET @user_id = (SELECT id FROM users WHERE email = 'lochaydeguerreiro@hotmail.com');

-- 2. Se usuário não existir, criar
INSERT IGNORE INTO users (email, nome, senha) VALUES
('lochaydeguerreiro@hotmail.com', 'Lochayde Guerreiro', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- 3. Buscar o ID do usuário (criado ou existente)
SET @user_id = (SELECT id FROM users WHERE email = 'lochaydeguerreiro@hotmail.com');

-- 4. Limpar materiais existentes do usuário (opcional)
DELETE FROM user_materials WHERE user_id = @user_id;

-- 5. Adicionar todos os materiais do Pacote Premium
-- Produto Principal: Libido Renovada (ID 1)
INSERT IGNORE INTO user_materials (user_id, material_id, liberado_em) VALUES
(@user_id, 1, NOW());

-- Order Bump: 5 Toques Mágicos (ID 6)
INSERT IGNORE INTO user_materials (user_id, material_id, liberado_em) VALUES
(@user_id, 6, NOW());

-- Pacote Premium: Versão em Áudio (ID 7)
INSERT IGNORE INTO user_materials (user_id, material_id, liberado_em) VALUES
(@user_id, 7, NOW());

-- Bônus 1: O Segredo da Resistência (ID 9)
INSERT IGNORE INTO user_materials (user_id, material_id, liberado_em) VALUES
(@user_id, 9, NOW());

-- Bônus 2: Sem Desejo Nunca Mais (ID 8)
INSERT IGNORE INTO user_materials (user_id, material_id, liberado_em) VALUES
(@user_id, 8, NOW());

-- 6. Verificar se os materiais foram adicionados
SELECT 
    u.email,
    u.nome,
    COUNT(um.id) as total_materiais
FROM users u
LEFT JOIN user_materials um ON u.id = um.user_id
WHERE u.email = 'lochaydeguerreiro@hotmail.com'
GROUP BY u.id;

-- 7. Listar todos os materiais liberados
SELECT 
    m.id,
    m.titulo,
    m.tipo,
    um.liberado_em,
    CASE 
        WHEN m.titulo = 'Guia Completo de Marketing Digital' THEN 'Libido Renovada (Principal)'
        WHEN m.titulo = 'Curso de Vendas Online' THEN '5 Toques Magicos (Order Bump)'
        WHEN m.titulo = 'Meditação para Iniciantes' THEN 'Versao em Audio (Premium)'
        WHEN m.titulo = 'Ebook de Receitas Saudáveis' THEN 'O Segredo da Resistencia (Bonus)'
        WHEN m.titulo = 'Tutorial de Fotografia' THEN 'Sem Desejo Nunca Mais (Bonus)'
        ELSE m.titulo
    END as descricao_compra
FROM user_materials um
JOIN materials m ON um.material_id = m.id
WHERE um.user_id = @user_id
ORDER BY um.liberado_em DESC;

-- 8. Resumo da compra simulada
SELECT 
    'PACOTE PREMIUM SIMULADO' as status,
    'lochaydeguerreiro@hotmail.com' as cliente,
    '5 materiais liberados' as resultado,
    NOW() as data_simulacao;
