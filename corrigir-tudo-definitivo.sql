-- Script DEFINITIVO para corrigir tudo
-- Execute este script para garantir que o dashboard funcione

-- 1. Limpar dados existentes para evitar conflitos
DELETE FROM user_purchases WHERE user_id = 1;
DELETE FROM user_materials WHERE user_id = 1;

-- 2. Garantir que o usuário existe
INSERT IGNORE INTO users (id, email, senha, nome, criado_em)
VALUES (1, 'lochaydeguerreiro@hotmail.com', '$2y$10$1GPUKgTcIXXCG0FJwlvO.uPoR4w4Ql2dnZLPjOwXzN20.dBoJKFnO', 'Lochayde Guerreiro', NOW());

-- 3. Garantir que os materiais existem com caminhos corretos
INSERT IGNORE INTO materials (id, titulo, descricao, caminho, tipo, liberado_em, criado_em)
VALUES 
(6, 'O Guia dos 5 Toques Mágicos', 'Order Bump - Guia rápido dos 5 toques mágicos', 'ebooks/o-guia-dos-5-toques-magicos.html', 'ebook', NOW(), NOW()),
(7, 'Libido Renovada', 'Produto Principal - O Plano de 21 Dias para Casais', 'ebooks/libido-renovada.html', 'ebook', NOW(), NOW()),
(8, 'Sem Desejo Nunca Mais', 'Bônus - Como usar sua sensualidade', 'ebooks/sem-desejo-nunca-mais.html', 'ebook', NOW(), NOW()),
(9, 'O Segredo da Resistência', 'Bônus - Guia prático para durar mais tempo', 'ebooks/o-segredo-da-resistencia.html', 'ebook', NOW(), NOW()),
(10, 'Libido Renovada - Áudio', 'Versão em áudio do produto principal', 'audios/libido-renovada-o-plano-de-acao-de-21-dias-para-reacender-a-intimidade-e-a-libido.mp3', 'audio', NOW(), NOW());

-- 4. Garantir que o mapeamento de produtos existe
INSERT IGNORE INTO product_material_mapping (hotmart_product_id, material_id, created_at)
VALUES 
('E101649402I', 7, NOW()),  -- Libido Renovada (Main)
('F101670521N', 6, NOW()),  -- 5 Toques Mágicos (Order Bump)
('V101660433I', 10, NOW()), -- Versão em Áudio (Downsell)
('R101782112U', 9, NOW()),  -- O Segredo da Resistência (Bonus)
('D101782229U', 8, NOW()),  -- Sem Desejo Nunca Mais (Bonus)
('A101789933P', 7, NOW()),  -- Pacote Premium (Upsell) - libera o material principal
('A101789933P', 9, NOW()),  -- Pacote Premium (Upsell) - libera bônus 1
('A101789933P', 8, NOW());  -- Pacote Premium (Upsell) - libera bônus 2

-- 5. Adicionar compras do usuário
INSERT INTO user_purchases (user_id, hotmart_transaction, hotmart_product_id, item_type, item_name, material_id, purchase_date, status)
VALUES 
(1, 'SIMULATED_TXN_001', 'E101649402I', 'main', 'Libido Renovada: O Plano de 21 Dias para Casais', 7, NOW(), 'active'),
(1, 'SIMULATED_TXN_001', 'F101670521N', 'order_bump', 'O Guia Rápido dos 5 Toques Mágicos', 6, NOW(), 'active'),
(1, 'SIMULATED_TXN_001', 'V101660433I', 'downsell', 'Versão em Áudio: Libido Renovada', 10, NOW(), 'active'),
(1, 'SIMULATED_TXN_001', 'R101782112U', 'bonus', 'O Segredo da Resistência', 9, NOW(), 'active'),
(1, 'SIMULATED_TXN_001', 'D101782229U', 'bonus', 'Sem Desejo Nunca Mais', 8, NOW(), 'active'),
(1, 'SIMULATED_TXN_001', 'A101789933P', 'upsell', 'Pacote PREMIUM - Libido Renovado', 7, NOW(), 'active');

-- 6. Verificação final
SELECT 'VERIFICAÇÃO FINAL' as status;
SELECT COUNT(*) as total_materiais FROM materials WHERE id IN (6,7,8,9,10);
SELECT COUNT(*) as total_mapeamentos FROM product_material_mapping;
SELECT COUNT(*) as total_compras FROM user_purchases WHERE user_id = 1 AND status = 'active';

-- 7. Teste da query do dashboard
SELECT 'TESTE DA QUERY DO DASHBOARD' as status;
SELECT 
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
