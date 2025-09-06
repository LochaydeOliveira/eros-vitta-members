-- Script para adicionar e-books em HTML no banco de dados

-- Exemplo 1: E-book "Guia 5 Toques Mágicos"
INSERT INTO materials (titulo, tipo, caminho, descricao) VALUES
('5 Toques Mágicos', 'ebook', 'ebooks/guia-5-toques-magicos.html', 'Um Caminho Simples para Reacender a Intimidade e a Conexão no Dia a Dia');

-- Exemplo 2: E-book "Plano de 21 Dias"
INSERT INTO materials (titulo, tipo, caminho, descricao) VALUES
('Libido Renovada - O Plano de 21 Dias Para Casais', 'ebook', 'ebooks/libido-renovada.html', 'O Plano de Ação de 21 Dias para Reacender a Intimidade e a Libido');

-- Exemplo 3: E-book "Diário do Desejo"
INSERT INTO materials (titulo, tipo, caminho, descricao) VALUES
('Sem Desejo Nunca Mais', 'ebook', 'ebooks/sem-desejo-nunca-mais.html', 'Descubra como usar sua sensualidade para viver uma paixão que não acaba mais');

INSERT INTO materials (titulo, tipo, caminho, descricao) VALUES
('O Segredo da Resistência', 'ebook', 'ebooks/o-segredo-da-resistencia.html', 'O Guia Para Ele Durar Mais Tempo na Cama');


-- Liberar os e-books para o usuário de teste (ID 1)
-- Usando INSERT IGNORE para evitar erros de duplicata
INSERT IGNORE INTO user_materials (user_id, material_id) VALUES
(1, 6),  -- 5 Toques Mágicos
(1, 7),  -- Libido Renovada
(1, 8),  -- Sem Desejo Nunca Mais
(1, 9);  -- O Segredo da Resistência

-- Alternativa: Usar INSERT ... ON DUPLICATE KEY UPDATE
-- INSERT INTO user_materials (user_id, material_id) VALUES
-- (1, 6), (1, 7), (1, 8), (1, 9)
-- ON DUPLICATE KEY UPDATE user_id = user_id;
