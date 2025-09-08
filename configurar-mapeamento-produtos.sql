-- Script para configurar o mapeamento entre produtos da Hotmart e materiais da área de membros
-- Execute este script no seu banco de dados

-- 1. Criar tabela de mapeamento de produtos
CREATE TABLE IF NOT EXISTS product_material_mapping (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hotmart_product_id VARCHAR(50) UNIQUE NOT NULL,
    hotmart_product_name VARCHAR(200) NOT NULL,
    material_id INT,
    material_type ENUM('main', 'order_bump', 'upsell', 'downsell', 'bonus') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (material_id) REFERENCES materials(id)
);

-- 2. Criar tabela de compras do usuário
CREATE TABLE IF NOT EXISTS user_purchases (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    hotmart_transaction VARCHAR(100) NOT NULL,
    hotmart_product_id VARCHAR(50) NOT NULL,
    item_type ENUM('main', 'order_bump', 'upsell', 'downsell', 'bonus') NOT NULL,
    item_name VARCHAR(200) NOT NULL,
    material_id INT,
    purchase_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'refunded', 'cancelled') DEFAULT 'active',
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (material_id) REFERENCES materials(id),
    INDEX idx_user_purchases (user_id, status),
    INDEX idx_hotmart_product (hotmart_product_id)
);

-- 3. Inserir mapeamentos dos produtos
INSERT INTO product_material_mapping (hotmart_product_id, hotmart_product_name, material_id, material_type) VALUES
-- Produto Principal
('E101649402I', 'Libido Renovada: O Plano de 21 Dias para Casais', 1, 'main'),

-- Order Bump
('F101670521N', 'O Guia Rápido dos 5 Toques Mágicos', 6, 'order_bump'),

-- Upsell (Pacote Premium - inclui múltiplos materiais)
('A101789933P', 'Pacote PREMIUM - Libido Renovado', NULL, 'upsell'),

-- Downsell
('V101660433I', 'Versão em Áudio: Libido Renovada - O Plano de 21 Dias Para Casais', 7, 'downsell'),

-- Bônus do Pacote Premium
('R101782112U', 'O Segredo da Resistência: O guia prático para durar mais tempo na cama', 9, 'bonus'),
('D101782229U', 'Sem Desejo Nunca Mais! Descubra como usar sua sensualidade para viver uma paixão que não acaba mais', 8, 'bonus');

-- 4. Criar tabela para controlar materiais incluídos no Pacote Premium
CREATE TABLE IF NOT EXISTS upsell_package_materials (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hotmart_product_id VARCHAR(50) NOT NULL,
    material_id INT NOT NULL,
    material_type ENUM('main', 'audio', 'bonus') NOT NULL,
    FOREIGN KEY (material_id) REFERENCES materials(id)
);

-- 5. Inserir materiais incluídos no Pacote Premium
INSERT INTO upsell_package_materials (hotmart_product_id, material_id, material_type) VALUES
-- Ebook principal
('A101789933P', 1, 'main'),
-- Versão em áudio
('A101789933P', 7, 'audio'),
-- Ebooks de bônus
('A101789933P', 9, 'bonus'), -- O Segredo da Resistência
('A101789933P', 8, 'bonus'); -- Sem Desejo Nunca Mais

-- 6. Verificar se os materiais existem na tabela materials
-- (Execute este SELECT para verificar se os IDs dos materiais estão corretos)
SELECT 
    m.id,
    m.titulo,
    m.tipo,
    pmm.hotmart_product_id,
    pmm.hotmart_product_name,
    pmm.material_type
FROM materials m
LEFT JOIN product_material_mapping pmm ON m.id = pmm.material_id
ORDER BY m.id;
