-- Script para liberar o Pacote Premium para o usuário Lochayde Guerreiro
-- Execute este script para simular a compra do Pacote Premium

-- 1. Verificar usuário atual
SELECT 
    'USUÁRIO ATUAL' as status,
    id,
    nome,
    email
FROM users 
WHERE email = 'lochaydeguerreiro@hotmail.com';

-- 2. Verificar materiais atuais do usuário
SELECT 
    'MATERIAIS ATUAIS' as status,
    COUNT(*) as total_materiais
FROM user_materials 
WHERE user_id = 1;

-- 3. Adicionar compra do Pacote Premium (A101789933P)
INSERT INTO user_purchases (
    user_id, 
    hotmart_transaction, 
    hotmart_product_id, 
    item_type, 
    item_name, 
    material_id, 
    purchase_date, 
    status
) VALUES (
    1, -- user_id do Lochayde
    'TXN_PREMIUM_' || UNIX_TIMESTAMP(), -- transaction única
    'A101789933P', -- ID do Pacote Premium
    'upsell', -- tipo do item
    'Pacote PREMIUM - Libido Renovado', -- nome do item
    NULL, -- material_id será NULL para pacotes
    NOW(), -- data da compra
    'active' -- status ativo
);

-- 4. Adicionar todos os materiais incluídos no Pacote Premium
-- Material 7: Libido Renovada (audio)
INSERT INTO user_purchases (
    user_id, 
    hotmart_transaction, 
    hotmart_product_id, 
    item_type, 
    item_name, 
    material_id, 
    purchase_date, 
    status
) VALUES (
    1,
    'TXN_PREMIUM_' || UNIX_TIMESTAMP(),
    'A101789933P',
    'upsell',
    'Pacote PREMIUM - Libido Renovado',
    7, -- Libido Renovada
    NOW(),
    'active'
);

-- Material 9: O Segredo da Resistência (bonus)
INSERT INTO user_purchases (
    user_id, 
    hotmart_transaction, 
    hotmart_product_id, 
    item_type, 
    item_name, 
    material_id, 
    purchase_date, 
    status
) VALUES (
    1,
    'TXN_PREMIUM_' || UNIX_TIMESTAMP(),
    'A101789933P',
    'upsell',
    'Pacote PREMIUM - Libido Renovado',
    9, -- O Segredo da Resistência
    NOW(),
    'active'
);

-- Material 8: Sem Desejo Nunca Mais (bonus)
INSERT INTO user_purchases (
    user_id, 
    hotmart_transaction, 
    hotmart_product_id, 
    item_type, 
    item_name, 
    material_id, 
    purchase_date, 
    status
) VALUES (
    1,
    'TXN_PREMIUM_' || UNIX_TIMESTAMP(),
    'A101789933P',
    'upsell',
    'Pacote PREMIUM - Libido Renovado',
    8, -- Sem Desejo Nunca Mais
    NOW(),
    'active'
);

-- 5. Verificar compras adicionadas
SELECT 
    'COMPRAS ADICIONADAS' as status,
    COUNT(*) as total_compras
FROM user_purchases 
WHERE user_id = 1 AND hotmart_product_id = 'A101789933P';

-- 6. Verificar materiais liberados via view
SELECT 
    'MATERIAIS LIBERADOS' as status,
    material_id,
    titulo,
    item_type,
    purchase_date,
    can_download
FROM user_dashboard_materials 
WHERE user_id = 1
ORDER BY purchase_date DESC;

-- 7. Resumo final
SELECT 
    'PACOTE PREMIUM LIBERADO' as status,
    'Usuário Lochayde Guerreiro agora tem acesso ao Pacote Premium completo' as resultado,
    NOW() as data_liberacao;
