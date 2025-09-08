-- Script SIMPLES para adicionar materiais ao usuário
-- Execute este script para liberar os materiais

-- 1. Limpar compras existentes (opcional)
-- DELETE FROM user_purchases WHERE user_id = 1;

-- 2. Adicionar compra do produto principal (Libido Renovada)
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
    'TXN_MAIN_12345', 
    'E101649402I', 
    'main', 
    'Libido Renovada: O Plano de 21 Dias para Casais', 
    7, 
    NOW(), 
    'active'
);

-- 3. Adicionar compra do order bump (5 Toques Mágicos)
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
    'TXN_ORDER_BUMP_12345', 
    'F101670521N', 
    'order_bump', 
    'O Guia Rápido dos 5 Toques Mágicos', 
    6, 
    NOW(), 
    'active'
);

-- 4. Adicionar compra do downsell (Versão em Áudio)
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
    'TXN_DOWNSELL_12345', 
    'V101660433I', 
    'downsell', 
    'Versão em Áudio: Libido Renovada - O Plano de 21 Dias Para Casais', 
    10, 
    NOW(), 
    'active'
);

-- 5. Adicionar compra do bônus 1 (O Segredo da Resistência)
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
    'TXN_BONUS1_12345', 
    'R101782112U', 
    'bonus', 
    'O Segredo da Resistência: O guia prático para durar mais tempo na cama', 
    9, 
    NOW(), 
    'active'
);

-- 6. Adicionar compra do bônus 2 (Sem Desejo Nunca Mais)
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
    'TXN_BONUS2_12345', 
    'D101782229U', 
    'bonus', 
    'Sem Desejo Nunca Mais! Descubra como usar sua sensualidade para viver uma paixão que não acaba mais', 
    8, 
    NOW(), 
    'active'
);

-- 7. Verificar resultado
SELECT 
    'MATERIAIS ADICIONADOS' as status,
    COUNT(*) as total_compras
FROM user_purchases 
WHERE user_id = 1;

-- 8. Listar materiais liberados
SELECT 
    up.hotmart_product_id,
    up.item_type,
    m.titulo,
    m.tipo,
    up.purchase_date
FROM user_purchases up
LEFT JOIN product_material_mapping pmm ON up.hotmart_product_id = pmm.hotmart_product_id
LEFT JOIN materials m ON pmm.material_id = m.id
WHERE up.user_id = 1 AND up.status = 'active' AND m.id IS NOT NULL
ORDER BY up.purchase_date DESC;
