-- Script para adicionar materiais ao usuário Lochayde Guerreiro
-- Execute este script para liberar os materiais

-- 1. Verificar usuário atual
SELECT 
    'USUÁRIO ATUAL' as status,
    id,
    nome,
    email
FROM users 
WHERE email = 'lochaydeguerreiro@hotmail.com';

-- 2. Verificar materiais disponíveis
SELECT 
    'MATERIAIS DISPONÍVEIS' as status,
    id,
    titulo,
    tipo,
    is_active
FROM materials
ORDER BY id;

-- 3. Verificar compras atuais
SELECT 
    'COMPRAS ATUAIS' as status,
    COUNT(*) as total_compras
FROM user_purchases 
WHERE user_id = 1;

-- 4. Adicionar compra do produto principal (Libido Renovada)
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
    'TXN_MAIN_' || UNIX_TIMESTAMP(), -- transaction única
    'E101649402I', -- ID do produto principal
    'main', -- tipo do item
    'Libido Renovada: O Plano de 21 Dias para Casais', -- nome do item
    7, -- material_id (Libido Renovada)
    NOW(), -- data da compra
    'active' -- status ativo
);

-- 5. Adicionar compra do order bump (5 Toques Mágicos)
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
    'TXN_ORDER_BUMP_' || UNIX_TIMESTAMP(),
    'F101670521N', -- ID do order bump
    'order_bump',
    'O Guia Rápido dos 5 Toques Mágicos',
    6, -- material_id (5 Toques Mágicos)
    NOW(),
    'active'
);

-- 6. Adicionar compra do downsell (Versão em Áudio)
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
    'TXN_DOWNSELL_' || UNIX_TIMESTAMP(),
    'V101660433I', -- ID do downsell
    'downsell',
    'Versão em Áudio: Libido Renovada - O Plano de 21 Dias Para Casais',
    10, -- material_id (Versão em Áudio)
    NOW(),
    'active'
);

-- 7. Adicionar compra do bônus 1 (O Segredo da Resistência)
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
    'TXN_BONUS1_' || UNIX_TIMESTAMP(),
    'R101782112U', -- ID do bônus 1
    'bonus',
    'O Segredo da Resistência: O guia prático para durar mais tempo na cama',
    9, -- material_id (O Segredo da Resistência)
    NOW(),
    'active'
);

-- 8. Adicionar compra do bônus 2 (Sem Desejo Nunca Mais)
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
    'TXN_BONUS2_' || UNIX_TIMESTAMP(),
    'D101782229U', -- ID do bônus 2
    'bonus',
    'Sem Desejo Nunca Mais! Descubra como usar sua sensualidade para viver uma paixão que não acaba mais',
    8, -- material_id (Sem Desejo Nunca Mais)
    NOW(),
    'active'
);

-- 9. Verificar compras adicionadas
SELECT 
    'COMPRAS ADICIONADAS' as status,
    COUNT(*) as total_compras
FROM user_purchases 
WHERE user_id = 1;

-- 10. Verificar materiais liberados
SELECT 
    'MATERIAIS LIBERADOS' as status,
    up.hotmart_product_id,
    up.item_type,
    up.item_name,
    m.titulo,
    m.tipo,
    up.purchase_date
FROM user_purchases up
LEFT JOIN product_material_mapping pmm ON up.hotmart_product_id = pmm.hotmart_product_id
LEFT JOIN materials m ON pmm.material_id = m.id
WHERE up.user_id = 1 AND up.status = 'active' AND m.id IS NOT NULL
ORDER BY up.purchase_date DESC;

-- 11. Resumo final
SELECT 
    'MATERIAIS ADICIONADOS COM SUCESSO' as status,
    'Usuário Lochayde Guerreiro agora tem acesso a todos os materiais' as resultado,
    NOW() as data_adicao;
