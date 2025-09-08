-- Script para criar um usuário de teste na área de membros
-- Execute este script no seu banco de dados

-- 1. Inserir usuário de teste (se não existir)
INSERT IGNORE INTO users (email, nome, senha) VALUES
('teste@exemplo.com', 'Usuário Teste', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- 2. Buscar o ID do usuário (criado ou existente)
SET @user_id = (SELECT id FROM users WHERE email = 'teste@exemplo.com');

-- 3. Adicionar compras de teste para o usuário
-- Produto Principal: Libido Renovada
INSERT IGNORE INTO user_purchases (user_id, hotmart_transaction, hotmart_product_id, item_type, item_name, material_id, purchase_date, status) VALUES
(@user_id, 'TXN_TESTE_001', 'E101649402I', 'main', 'Libido Renovada: O Plano de 21 Dias para Casais', 1, NOW(), 'active'),

-- Order Bump: 5 Toques Mágicos
(@user_id, 'TXN_TESTE_001', 'F101670521N', 'order_bump', 'O Guia Rápido dos 5 Toques Mágicos', 6, NOW(), 'active'),

-- Pacote Premium (Upsell) - inclui múltiplos materiais
(@user_id, 'TXN_TESTE_002', 'A101789933P', 'upsell', 'Pacote PREMIUM - Libido Renovado', NULL, NOW(), 'active'),

-- Materiais incluídos no Pacote Premium
(@user_id, 'TXN_TESTE_002', 'A101789933P', 'upsell', 'Pacote PREMIUM - Libido Renovado', 1, NOW(), 'active'), -- Ebook principal
(@user_id, 'TXN_TESTE_002', 'A101789933P', 'upsell', 'Pacote PREMIUM - Libido Renovado', 7, NOW(), 'active'), -- Versão em áudio
(@user_id, 'TXN_TESTE_002', 'A101789933P', 'upsell', 'Pacote PREMIUM - Libido Renovado', 9, NOW(), 'active'), -- O Segredo da Resistência
(@user_id, 'TXN_TESTE_002', 'A101789933P', 'upsell', 'Pacote PREMIUM - Libido Renovado', 8, NOW(), 'active'); -- Sem Desejo Nunca Mais

-- 4. Verificar se o usuário foi criado corretamente
SELECT 
    u.id,
    u.email,
    u.nome,
    u.status,
    u.created_at,
    COUNT(up.id) as total_compras
FROM users u
LEFT JOIN user_purchases up ON u.id = up.user_id AND up.status = 'active'
WHERE u.email = 'teste@exemplo.com'
GROUP BY u.id;

-- 5. Verificar materiais liberados para o usuário
SELECT 
    m.id,
    m.titulo,
    m.tipo,
    up.item_type,
    up.purchase_date,
    up.hotmart_product_id
FROM user_purchases up
JOIN materials m ON up.material_id = m.id
WHERE up.user_id = @user_id 
AND up.status = 'active'
ORDER BY up.purchase_date DESC;

-- 6. Dados de login para teste:
-- Email: teste@exemplo.com
-- Senha: password
-- (A senha está criptografada com password_hash() do PHP)
