-- Script para testar o dashboard final
-- Execute este script para verificar se tudo está funcionando

-- 1. Verificar se o usuário existe e tem compras
SELECT 
    'USUÁRIO E COMPRAS' as status,
    u.id,
    u.nome,
    u.email,
    COUNT(up.id) as total_compras
FROM users u
LEFT JOIN user_purchases up ON u.id = up.user_id AND up.status = 'active'
WHERE u.email = 'lochaydeguerreiro@hotmail.com'
GROUP BY u.id, u.nome, u.email;

-- 2. Verificar se os materiais estão mapeados corretamente
SELECT 
    'MAPEAMENTO PRODUTOS-MATERIAIS' as status,
    pmm.hotmart_product_id,
    pmm.material_id,
    m.titulo,
    m.caminho,
    m.tipo
FROM product_material_mapping pmm
JOIN materials m ON pmm.material_id = m.id
ORDER BY pmm.material_id;

-- 3. Verificar as compras do usuário
SELECT 
    'COMPRAS DO USUÁRIO' as status,
    up.id,
    up.hotmart_product_id,
    up.item_type,
    up.item_name,
    up.material_id,
    up.purchase_date,
    up.status
FROM user_purchases up
WHERE up.user_id = 1 AND up.status = 'active'
ORDER BY up.purchase_date DESC;

-- 4. Simular a query do dashboard
SELECT 
    'QUERY DO DASHBOARD' as status,
    m.id,
    m.titulo,
    m.caminho,
    m.tipo,
    up.item_type,
    up.purchase_date,
    up.hotmart_product_id
FROM user_purchases up
LEFT JOIN product_material_mapping pmm ON up.hotmart_product_id = pmm.hotmart_product_id
LEFT JOIN materials m ON pmm.material_id = m.id
WHERE up.user_id = 1 AND up.status = 'active' AND m.id IS NOT NULL
ORDER BY up.purchase_date DESC;

-- 5. Verificar se os arquivos existem
SELECT 
    'VERIFICAÇÃO DE ARQUIVOS' as status,
    m.id,
    m.titulo,
    m.caminho,
    CASE 
        WHEN m.caminho LIKE 'ebooks/%' THEN 'ebooks/'
        WHEN m.caminho LIKE 'audios/%' THEN 'audios/'
        WHEN m.caminho LIKE 'videos/%' THEN 'videos/'
        ELSE 'outros/'
    END as pasta_esperada
FROM materials m
WHERE m.id IN (6, 7, 8, 9, 10)
ORDER BY m.id;

-- 6. Resumo final
SELECT 
    'TESTE CONCLUÍDO' as status,
    'Dashboard deve mostrar 5 materiais' as resultado,
    NOW() as data_teste;
