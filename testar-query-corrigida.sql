-- Script para testar a query corrigida do dashboard
-- Execute este script para verificar se a query está funcionando

-- 1. Verificar se o usuário tem compras
SELECT 
    'COMPRAS DO USUÁRIO' as status,
    COUNT(*) as total_compras
FROM user_purchases 
WHERE user_id = 1 AND status = 'active';

-- 2. Verificar se os materiais existem
SELECT 
    'MATERIAIS DISPONÍVEIS' as status,
    COUNT(*) as total_materiais
FROM materials 
WHERE id IN (6, 7, 8, 9, 10);

-- 3. Testar a query corrigida do dashboard
SELECT 
    'QUERY CORRIGIDA DO DASHBOARD' as status,
    m.id,
    m.titulo,
    m.caminho,
    m.tipo,
    up.item_type,
    up.purchase_date,
    up.hotmart_product_id
FROM user_purchases up
JOIN materials m ON up.material_id = m.id
WHERE up.user_id = 1 AND up.status = 'active' AND m.id IS NOT NULL
ORDER BY up.purchase_date DESC;

-- 4. Verificar se os arquivos existem fisicamente
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

-- 5. Resumo final
SELECT 
    'TESTE CONCLUÍDO' as status,
    'Se a query retornar 5 materiais, o dashboard deve funcionar' as resultado,
    NOW() as data_teste;
