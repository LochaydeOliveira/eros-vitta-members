-- Teste da query do dashboard
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
