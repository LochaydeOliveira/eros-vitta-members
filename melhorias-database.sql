-- MELHORIAS SUGERIDAS PARA O BANCO DE DADOS
-- Execute estas melhorias para otimizar a estrutura

-- 1. ADICIONAR ÍNDICES PARA PERFORMANCE
ALTER TABLE `user_purchases` 
ADD INDEX `idx_user_status_date` (`user_id`, `status`, `purchase_date`),
ADD INDEX `idx_hotmart_product_status` (`hotmart_product_id`, `status`);

ALTER TABLE `materials` 
ADD INDEX `idx_tipo_criado` (`tipo`, `criado_em`);

-- 2. ADICIONAR CAMPOS PARA AUDITORIA
ALTER TABLE `user_purchases` 
ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `refund_reason` TEXT NULL,
ADD COLUMN `refund_date` TIMESTAMP NULL;

-- 3. ADICIONAR TABELA DE LOGS DE ACESSO
CREATE TABLE `access_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `access_type` enum('view','download') NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_material` (`user_id`, `material_id`),
  KEY `idx_access_type` (`access_type`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `access_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `access_logs_ibfk_2` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- 4. ADICIONAR TABELA DE CONFIGURAÇÕES
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `description` text,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- 5. INSERIR CONFIGURAÇÕES PADRÃO
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES
('download_grace_period_days', '7', 'Período de carência para download de PDFs em dias'),
('max_downloads_per_day', '10', 'Máximo de downloads por usuário por dia'),
('session_timeout_minutes', '60', 'Timeout da sessão em minutos'),
('maintenance_mode', '0', 'Modo de manutenção (0=off, 1=on)');

-- 6. ADICIONAR CAMPO DE VERSÃO AOS MATERIAIS
ALTER TABLE `materials` 
ADD COLUMN `version` varchar(20) DEFAULT '1.0',
ADD COLUMN `file_size` bigint DEFAULT NULL,
ADD COLUMN `file_hash` varchar(64) DEFAULT NULL,
ADD COLUMN `is_active` tinyint(1) DEFAULT 1;

-- 7. ADICIONAR TABELA DE NOTIFICAÇÕES
CREATE TABLE `user_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_read` (`user_id`, `is_read`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `user_notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- 8. ADICIONAR CAMPOS DE SEGURANÇA AOS USUÁRIOS
ALTER TABLE `users` 
ADD COLUMN `last_login` timestamp NULL,
ADD COLUMN `login_attempts` int(11) DEFAULT 0,
ADD COLUMN `locked_until` timestamp NULL,
ADD COLUMN `two_factor_enabled` tinyint(1) DEFAULT 0,
ADD COLUMN `two_factor_secret` varchar(32) NULL;

-- 9. CRIAR VIEW PARA DASHBOARD (PERFORMANCE)
CREATE VIEW `user_dashboard_materials` AS
SELECT DISTINCT 
    u.id as user_id,
    m.id as material_id,
    m.titulo,
    m.tipo,
    m.caminho,
    m.descricao,
    up.purchase_date,
    up.item_type,
    up.hotmart_product_id,
    up.status as purchase_status,
    CASE 
        WHEN m.tipo = 'ebook' AND DATEDIFF(NOW(), up.purchase_date) >= 7 
        THEN 1 ELSE 0 
    END as can_download
FROM users u
JOIN user_purchases up ON u.id = up.user_id
LEFT JOIN product_material_mapping pmm ON up.hotmart_product_id = pmm.hotmart_product_id
LEFT JOIN materials m ON pmm.material_id = m.id
WHERE up.status = 'active' AND m.id IS NOT NULL

UNION

SELECT DISTINCT 
    u.id as user_id,
    m.id as material_id,
    m.titulo,
    m.tipo,
    m.caminho,
    m.descricao,
    up.purchase_date,
    up.item_type,
    up.hotmart_product_id,
    up.status as purchase_status,
    CASE 
        WHEN m.tipo = 'ebook' AND DATEDIFF(NOW(), up.purchase_date) >= 7 
        THEN 1 ELSE 0 
    END as can_download
FROM users u
JOIN user_purchases up ON u.id = up.user_id
JOIN upsell_package_materials upm ON up.hotmart_product_id = upm.hotmart_product_id
JOIN materials m ON upm.material_id = m.id
WHERE up.status = 'active' AND up.item_type = 'upsell';

-- 10. ADICIONAR TRIGGERS PARA AUDITORIA
DELIMITER $$

CREATE TRIGGER `tr_user_purchases_audit` 
AFTER UPDATE ON `user_purchases`
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO access_logs (user_id, material_id, access_type, ip_address, user_agent)
        VALUES (NEW.user_id, NEW.material_id, 'status_change', 'SYSTEM', 'TRIGGER');
    END IF;
END$$

DELIMITER ;

-- 11. PROCEDURE PARA LIMPEZA AUTOMÁTICA
DELIMITER $$

CREATE PROCEDURE `sp_cleanup_old_logs`()
BEGIN
    -- Remove logs de acesso com mais de 90 dias
    DELETE FROM access_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
    
    -- Remove notificações lidas com mais de 30 dias
    DELETE FROM user_notifications WHERE is_read = 1 AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
    
    -- Atualiza estatísticas
    SELECT 'Limpeza concluída' as status, NOW() as executed_at;
END$$

DELIMITER ;

-- 12. EVENT SCHEDULER PARA LIMPEZA AUTOMÁTICA (executar manualmente)
-- SET GLOBAL event_scheduler = ON;
-- CREATE EVENT ev_daily_cleanup
-- ON SCHEDULE EVERY 1 DAY
-- STARTS CURRENT_TIMESTAMP
-- DO CALL sp_cleanup_old_logs();

-- 13. VERIFICAR ESTRUTURA FINAL
SELECT 
    'MELHORIAS APLICADAS' as status,
    'Estrutura otimizada com logs, auditoria e performance' as resultado,
    NOW() as data_aplicacao;
