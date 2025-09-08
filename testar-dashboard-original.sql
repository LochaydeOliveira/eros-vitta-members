-- Script para testar se o dashboard original está funcionando
-- Execute este script para verificar os dados

-- 1. Verificar se os caminhos foram corrigidos
SELECT 
    'MATERIAIS COM CAMINHOS CORRIGIDOS' as status,
    id,
    titulo,
    caminho,
    tipo
FROM materials
ORDER BY id;

-- 2. Verificar se as compras estão ativas
SELECT 
    'COMPRAS ATIVAS DO USUÁRIO 1' as status,
    COUNT(*) as total_compras
FROM user_purchases 
WHERE user_id = 1 AND status = 'active';

-- 3. Verificar se os materiais estão sendo encontrados pela query do dashboard
SELECT 
    'MATERIAIS ENCONTRADOS PELA QUERY DO DASHBOARD' as status,
    COUNT(*) as total_materiais
FROM user_purchases up
LEFT JOIN product_material_mapping pmm ON up.hotmart_product_id = pmm.hotmart_product_id
LEFT JOIN materials m ON pmm.material_id = m.id
WHERE up.user_id = 1 AND up.status = 'active' AND m.id IS NOT NULL;

-- 4. Listar os materiais que devem aparecer no dashboard
SELECT 
    'MATERIAIS QUE DEVEM APARECER NO DASHBOARD' as status,
    m.id,
    m.titulo,
    m.caminho,
    m.tipo,
    up.item_type,
    up.purchase_date
FROM user_purchases up
LEFT JOIN product_material_mapping pmm ON up.hotmart_product_id = pmm.hotmart_product_id
LEFT JOIN materials m ON pmm.material_id = m.id
WHERE up.user_id = 1 AND up.status = 'active' AND m.id IS NOT NULL
ORDER BY up.purchase_date DESC;

-- 5. Verificar se os arquivos existem fisicamente
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
    END as pasta,
    'Verificar se existe em storage/' as observacao
FROM materials m
WHERE m.id IN (6, 7, 8, 9, 10)
ORDER BY m.id;

-- 6. Resumo final
SELECT 
    'TESTE CONCLUÍDO' as status,
    'Se todos os dados estão corretos, o dashboard deve funcionar' as resultado,
    NOW() as data_teste;
