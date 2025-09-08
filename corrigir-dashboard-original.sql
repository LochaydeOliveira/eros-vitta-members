-- Script para corrigir o dashboard original
-- Execute este script para ajustar os caminhos corretos dos arquivos

-- 1. Verificar materiais atuais
SELECT 
    'MATERIAIS ANTES DA CORREÇÃO' as status,
    id,
    titulo,
    caminho,
    tipo
FROM materials
ORDER BY id;

-- 2. Corrigir todos os caminhos dos materiais
UPDATE materials 
SET caminho = 'ebooks/o-guia-dos-5-toques-magicos.html'
WHERE id = 6 AND titulo = '5 Toques Mágicos';

UPDATE materials 
SET caminho = 'ebooks/libido-renovada.html'
WHERE id = 7 AND titulo = 'Libido Renovada - O Plano de 21 Dias Para Casais';

UPDATE materials 
SET caminho = 'ebooks/sem-desejo-nunca-mais.html'
WHERE id = 8 AND titulo = 'Sem Desejo Nunca Mais';

UPDATE materials 
SET caminho = 'ebooks/o-segredo-da-resistencia.html'
WHERE id = 9 AND titulo = 'O Segredo da Resistência';

UPDATE materials 
SET caminho = 'audios/libido-renovada-o-plano-de-acao-de-21-dias-para-reacender-a-intimidade-e-a-libido.mp3'
WHERE id = 10 AND titulo = 'Versão em Áudio: Libido Renovada - O Plano de 21 Dias Para Casais';

-- 3. Verificar correções aplicadas
SELECT 
    'MATERIAIS APÓS CORREÇÃO' as status,
    id,
    titulo,
    caminho,
    tipo
FROM materials
ORDER BY id;

-- 4. Verificar se os materiais estão sendo encontrados corretamente
SELECT 
    'TESTE DE BUSCA DE MATERIAIS' as status,
    COUNT(*) as total_materiais_encontrados
FROM user_purchases up
LEFT JOIN product_material_mapping pmm ON up.hotmart_product_id = pmm.hotmart_product_id
LEFT JOIN materials m ON pmm.material_id = m.id
WHERE up.user_id = 1 AND up.status = 'active' AND m.id IS NOT NULL;

-- 5. Listar materiais que serão exibidos no dashboard
SELECT 
    'MATERIAIS QUE APARECERÃO NO DASHBOARD' as status,
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

-- 6. Resumo final
SELECT 
    'CORREÇÕES CONCLUÍDAS' as status,
    'Caminhos dos arquivos corrigidos. Dashboard deve funcionar agora.' as resultado,
    NOW() as data_correcao;
