-- Script SIMPLES para testar o dashboard
-- Execute este script completo

-- 1. Verificar compras do usuário
SELECT 'COMPRAS DO USUÁRIO' as status, COUNT(*) as total FROM user_purchases WHERE user_id = 1 AND status = 'active';

-- 2. Verificar materiais
SELECT 'MATERIAIS DISPONÍVEIS' as status, COUNT(*) as total FROM materials WHERE id IN (6,7,8,9,10);

-- 3. Query do dashboard (CORRIGIDA)
SELECT 
    m.id,
    m.titulo,
    m.caminho,
    m.tipo,
    up.item_type,
    up.purchase_date
FROM user_purchases up
JOIN materials m ON up.material_id = m.id
WHERE up.user_id = 1 AND up.status = 'active' AND m.id IS NOT NULL
ORDER BY up.purchase_date DESC;

-- 4. Resumo
SELECT 'TESTE CONCLUÍDO - Se retornou 5 materiais, o dashboard deve funcionar' as resultado;
