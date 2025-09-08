-- Script para simular compra do Pacote Premium usando o sistema completo
-- Execute este script no seu banco de dados

-- 1. Verificar se o usuário existe
SET @user_id = (SELECT id FROM users WHERE email = 'lochaydeguerreiro@hotmail.com');

-- 2. Limpar compras existentes (opcional)
DELETE FROM user_purchases WHERE user_id = @user_id;

-- 3. Simular compra do Pacote Premium (Upsell)
-- Transação principal
INSERT INTO user_purchases (user_id, hotmart_transaction, hotmart_product_id, item_type, item_name, material_id, purchase_date, status) VALUES
-- Produto Principal: Libido Renovada
(@user_id, 'TXN_PREMIUM_001', 'E101649402I', 'main', 'Libido Renovada: O Plano de 21 Dias para Casais', 1, NOW(), 'active'),

-- Order Bump: 5 Toques Mágicos
(@user_id, 'TXN_PREMIUM_001', 'F101670521N', 'order_bump', 'O Guia Rápido dos 5 Toques Mágicos', 6, NOW(), 'active'),

-- Pacote Premium (Upsell) - inclui múltiplos materiais
(@user_id, 'TXN_PREMIUM_002', 'A101789933P', 'upsell', 'Pacote PREMIUM - Libido Renovado', NULL, NOW(), 'active'),

-- Materiais incluídos no Pacote Premium
(@user_id, 'TXN_PREMIUM_002', 'A101789933P', 'upsell', 'Pacote PREMIUM - Libido Renovado', 1, NOW(), 'active'), -- Ebook principal
(@user_id, 'TXN_PREMIUM_002', 'A101789933P', 'upsell', 'Pacote PREMIUM - Libido Renovado', 7, NOW(), 'active'), -- Versão em áudio
(@user_id, 'TXN_PREMIUM_002', 'A101789933P', 'upsell', 'Pacote PREMIUM - Libido Renovado', 9, NOW(), 'active'), -- O Segredo da Resistência
(@user_id, 'TXN_PREMIUM_002', 'A101789933P', 'upsell', 'Pacote PREMIUM - Libido Renovado', 8, NOW(), 'active'); -- Sem Desejo Nunca Mais

-- 4. Verificar compras registradas
SELECT 
    'COMPRAS REGISTRADAS' as status,
    COUNT(*) as total_compras
FROM user_purchases 
WHERE user_id = @user_id AND status = 'active';

-- 5. Listar todas as compras do usuário
SELECT 
    up.id,
    up.hotmart_product_id,
    up.item_type,
    up.item_name,
    up.purchase_date,
    CASE 
        WHEN up.hotmart_product_id = 'E101649402I' THEN 'Libido Renovada (Principal)'
        WHEN up.hotmart_product_id = 'F101670521N' THEN '5 Toques Magicos (Order Bump)'
        WHEN up.hotmart_product_id = 'A101789933P' THEN 'Pacote PREMIUM (Upsell)'
        ELSE up.item_name
    END as descricao_compra
FROM user_purchases up
WHERE up.user_id = @user_id AND up.status = 'active'
ORDER BY up.purchase_date DESC;

-- 6. Verificar materiais liberados
SELECT 
    'MATERIAIS LIBERADOS' as status,
    COUNT(*) as total_materiais
FROM user_materials 
WHERE user_id = @user_id;

-- 7. Listar materiais com descrição
SELECT 
    m.id,
    m.titulo,
    m.tipo,
    um.liberado_em,
    CASE 
        WHEN m.id = 1 THEN 'Libido Renovada (Principal)'
        WHEN m.id = 6 THEN '5 Toques Magicos (Order Bump)'
        WHEN m.id = 7 THEN 'Versao em Audio (Premium)'
        WHEN m.id = 9 THEN 'O Segredo da Resistencia (Bonus)'
        WHEN m.id = 8 THEN 'Sem Desejo Nunca Mais (Bonus)'
        ELSE m.titulo
    END as descricao_compra
FROM user_materials um
JOIN materials m ON um.material_id = m.id
WHERE um.user_id = @user_id
ORDER BY um.liberado_em DESC;

-- 8. Resumo final
SELECT 
    'PACOTE PREMIUM SIMULADO COM SUCESSO' as status,
    'lochaydeguerreiro@hotmail.com' as cliente,
    'Sistema completo de compras ativo' as resultado,
    NOW() as data_simulacao;
