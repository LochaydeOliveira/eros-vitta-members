-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Tempo de geração: 08/09/2025 às 06:32
-- Versão do servidor: 5.7.23-23
-- Versão do PHP: 8.1.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `paymen58_eros_vitta`
--

DELIMITER $$
--
-- Procedimentos
--
CREATE DEFINER=`paymen58`@`localhost` PROCEDURE `sp_cleanup_old_logs` ()   BEGIN
    -- Remove logs de acesso com mais de 90 dias
    DELETE FROM access_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
    
    -- Remove notificações lidas com mais de 30 dias
    DELETE FROM user_notifications WHERE is_read = 1 AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
    
    -- Atualiza estatísticas
    SELECT 'Limpeza concluída' as status, NOW() as executed_at;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `access_logs`
--

CREATE TABLE `access_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `access_type` enum('view','download') COLLATE utf8_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `user_agent` text COLLATE utf8_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `materials`
--

CREATE TABLE `materials` (
  `id` int(11) NOT NULL,
  `titulo` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
  `tipo` enum('ebook','video','audio') COLLATE utf8_unicode_ci NOT NULL,
  `caminho` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8_unicode_ci,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `version` varchar(20) COLLATE utf8_unicode_ci DEFAULT '1.0',
  `file_size` bigint(20) DEFAULT NULL,
  `file_hash` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `materials`
--

INSERT INTO `materials` (`id`, `titulo`, `tipo`, `caminho`, `descricao`, `criado_em`, `version`, `file_size`, `file_hash`, `is_active`) VALUES
(6, '5 Toques Mágicos', 'ebook', 'ebooks/guia-5-toques-magicos.html', 'Um Caminho Simples para Reacender a Intimidade e a Conexão no Dia a Dia', '2025-09-06 15:43:39', '1.0', NULL, NULL, 1),
(7, 'Libido Renovada - O Plano de 21 Dias Para Casais', 'ebook', 'ebooks/libido-renovada.html', 'O Plano de Ação de 21 Dias para Reacender a Intimidade e a Libido', '2025-09-06 15:43:39', '1.0', NULL, NULL, 1),
(8, 'Sem Desejo Nunca Mais', 'ebook', 'ebooks/sem-desejo-nunca-mais.html', 'Descubra como usar sua sensualidade para viver uma paixão que não acaba mais', '2025-09-06 15:43:39', '1.0', NULL, NULL, 1),
(9, 'O Segredo da Resistência', 'ebook', 'ebooks/o-segredo-da-resistencia.html', 'O Guia Para Ele Durar Mais Tempo na Cama', '2025-09-06 15:43:39', '1.0', NULL, NULL, 1),
(10, 'Versão em Áudio: Libido Renovada - O Plano de 21 Dias Para Casais', 'ebook', 'audios/libido-renovada-o-plano-de-acao-de-21-dias-para-reacender-a-intimidade-e-a-libido.mp3', 'Versão em Áudio: Libido Renovada - O Plano de 21 Dias Para Casais', '2025-09-06 15:43:39', '1.0', NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `product_material_mapping`
--

CREATE TABLE `product_material_mapping` (
  `id` int(11) NOT NULL,
  `hotmart_product_id` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `hotmart_product_name` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `material_id` int(11) DEFAULT NULL,
  `material_type` enum('main','order_bump','upsell','downsell','bonus') COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `product_material_mapping`
--

INSERT INTO `product_material_mapping` (`id`, `hotmart_product_id`, `hotmart_product_name`, `material_id`, `material_type`, `created_at`) VALUES
(1, 'E101649402I', 'Libido Renovada: O Plano de 21 Dias para Casais', 7, 'main', '2025-09-08 05:00:08'),
(2, 'F101670521N', 'O Guia Rápido dos 5 Toques Mágicos', 6, 'order_bump', '2025-09-08 05:00:08'),
(4, 'V101660433I', 'Versão em Áudio: Libido Renovada - O Plano de 21 Dias Para Casais', 10, 'downsell', '2025-09-08 05:00:08'),
(5, 'R101782112U', 'O Segredo da Resistência: O guia prático para durar mais tempo na cama', 9, 'bonus', '2025-09-08 05:00:08'),
(6, 'D101782229U', 'Sem Desejo Nunca Mais! Descubra como usar sua sensualidade para viver uma paixão que não acaba mais', 8, 'bonus', '2025-09-08 05:00:08');

-- --------------------------------------------------------

--
-- Estrutura para tabela `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `description`, `updated_at`) VALUES
(1, 'download_grace_period_days', '7', 'Período de carência para download de PDFs em dias', '2025-09-08 09:21:06'),
(2, 'max_downloads_per_day', '10', 'Máximo de downloads por usuário por dia', '2025-09-08 09:21:06'),
(3, 'session_timeout_minutes', '60', 'Timeout da sessão em minutos', '2025-09-08 09:21:06'),
(4, 'maintenance_mode', '0', 'Modo de manutenção (0=off, 1=on)', '2025-09-08 09:21:06');

-- --------------------------------------------------------

--
-- Estrutura para tabela `upsell_package_materials`
--

CREATE TABLE `upsell_package_materials` (
  `id` int(11) NOT NULL,
  `hotmart_product_id` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `material_id` int(11) NOT NULL,
  `material_type` enum('main','audio','bonus') COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `upsell_package_materials`
--

INSERT INTO `upsell_package_materials` (`id`, `hotmart_product_id`, `material_id`, `material_type`) VALUES
(2, 'A101789933P', 7, 'audio'),
(3, 'A101789933P', 9, 'bonus'),
(4, 'A101789933P', 8, 'bonus');

-- --------------------------------------------------------

--
-- Estrutura para tabela `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
  `senha` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `nome` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  `login_attempts` int(11) DEFAULT '0',
  `locked_until` timestamp NULL DEFAULT NULL,
  `two_factor_enabled` tinyint(1) DEFAULT '0',
  `two_factor_secret` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `users`
--

INSERT INTO `users` (`id`, `email`, `senha`, `nome`, `criado_em`, `last_login`, `login_attempts`, `locked_until`, `two_factor_enabled`, `two_factor_secret`) VALUES
(1, 'lochaydeguerreiro@hotmail.com', '$2y$10$1GPUKgTcIXXCG0FJwlvO.uPoR4w4Ql2dnZLPjOwXzN20.dBoJKFnO', 'Lochayde Guerreiro', '2025-09-08 06:15:30', NULL, 0, NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `user_dashboard_materials`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `user_dashboard_materials` (
`user_id` int(11)
,`material_id` int(11)
,`titulo` varchar(150)
,`tipo` varchar(5)
,`caminho` varchar(255)
,`descricao` mediumtext
,`purchase_date` timestamp
,`item_type` varchar(10)
,`hotmart_product_id` varchar(50)
,`purchase_status` varchar(9)
,`can_download` bigint(20)
);

-- --------------------------------------------------------

--
-- Estrutura para tabela `user_materials`
--

CREATE TABLE `user_materials` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `liberado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `user_materials`
--

INSERT INTO `user_materials` (`id`, `user_id`, `material_id`, `liberado_em`) VALUES
(33, 1, 6, '2025-09-08 07:50:44'),
(34, 1, 7, '2025-09-08 07:50:44'),
(35, 1, 9, '2025-09-08 07:50:44'),
(36, 1, 8, '2025-09-08 07:50:44');

-- --------------------------------------------------------

--
-- Estrutura para tabela `user_notifications`
--

CREATE TABLE `user_notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `message` text COLLATE utf8_unicode_ci NOT NULL,
  `type` enum('info','success','warning','error') COLLATE utf8_unicode_ci DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `user_purchases`
--

CREATE TABLE `user_purchases` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `hotmart_transaction` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `hotmart_product_id` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `item_type` enum('main','order_bump','upsell','downsell','bonus') COLLATE utf8_unicode_ci NOT NULL,
  `item_name` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `material_id` int(11) DEFAULT NULL,
  `purchase_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('active','refunded','cancelled') COLLATE utf8_unicode_ci DEFAULT 'active',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `refund_reason` text COLLATE utf8_unicode_ci,
  `refund_date` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Acionadores `user_purchases`
--
DELIMITER $$
CREATE TRIGGER `tr_user_purchases_audit` AFTER UPDATE ON `user_purchases` FOR EACH ROW BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO access_logs (user_id, material_id, access_type, ip_address, user_agent)
        VALUES (NEW.user_id, NEW.material_id, 'status_change', 'SYSTEM', 'TRIGGER');
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para view `user_dashboard_materials`
--
DROP TABLE IF EXISTS `user_dashboard_materials`;

CREATE ALGORITHM=UNDEFINED DEFINER=`paymen58`@`localhost` SQL SECURITY DEFINER VIEW `user_dashboard_materials`  AS SELECT DISTINCT `u`.`id` AS `user_id`, `m`.`id` AS `material_id`, `m`.`titulo` AS `titulo`, `m`.`tipo` AS `tipo`, `m`.`caminho` AS `caminho`, `m`.`descricao` AS `descricao`, `up`.`purchase_date` AS `purchase_date`, `up`.`item_type` AS `item_type`, `up`.`hotmart_product_id` AS `hotmart_product_id`, `up`.`status` AS `purchase_status`, (case when ((`m`.`tipo` = 'ebook') and ((to_days(now()) - to_days(`up`.`purchase_date`)) >= 7)) then 1 else 0 end) AS `can_download` FROM (((`users` `u` join `user_purchases` `up` on((`u`.`id` = `up`.`user_id`))) left join `product_material_mapping` `pmm` on((`up`.`hotmart_product_id` = `pmm`.`hotmart_product_id`))) left join `materials` `m` on((`pmm`.`material_id` = `m`.`id`))) WHERE ((`up`.`status` = 'active') AND (`m`.`id` is not null))union select distinct `u`.`id` AS `user_id`,`m`.`id` AS `material_id`,`m`.`titulo` AS `titulo`,`m`.`tipo` AS `tipo`,`m`.`caminho` AS `caminho`,`m`.`descricao` AS `descricao`,`up`.`purchase_date` AS `purchase_date`,`up`.`item_type` AS `item_type`,`up`.`hotmart_product_id` AS `hotmart_product_id`,`up`.`status` AS `purchase_status`,(case when ((`m`.`tipo` = 'ebook') and ((to_days(now()) - to_days(`up`.`purchase_date`)) >= 7)) then 1 else 0 end) AS `can_download` from (((`users` `u` join `user_purchases` `up` on((`u`.`id` = `up`.`user_id`))) join `upsell_package_materials` `upm` on((`up`.`hotmart_product_id` = `upm`.`hotmart_product_id`))) join `materials` `m` on((`upm`.`material_id` = `m`.`id`))) where ((`up`.`status` = 'active') and (`up`.`item_type` = 'upsell'))  ;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `access_logs`
--
ALTER TABLE `access_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_material` (`user_id`,`material_id`),
  ADD KEY `idx_access_type` (`access_type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `access_logs_ibfk_2` (`material_id`);

--
-- Índices de tabela `materials`
--
ALTER TABLE `materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tipo` (`tipo`),
  ADD KEY `idx_tipo_criado` (`tipo`,`criado_em`);

--
-- Índices de tabela `product_material_mapping`
--
ALTER TABLE `product_material_mapping`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `hotmart_product_id` (`hotmart_product_id`),
  ADD KEY `material_id` (`material_id`);

--
-- Índices de tabela `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Índices de tabela `upsell_package_materials`
--
ALTER TABLE `upsell_package_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `material_id` (`material_id`);

--
-- Índices de tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`);

--
-- Índices de tabela `user_materials`
--
ALTER TABLE `user_materials`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_material` (`user_id`,`material_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_material_id` (`material_id`);

--
-- Índices de tabela `user_notifications`
--
ALTER TABLE `user_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_read` (`user_id`,`is_read`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Índices de tabela `user_purchases`
--
ALTER TABLE `user_purchases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `material_id` (`material_id`),
  ADD KEY `idx_user_purchases` (`user_id`,`status`),
  ADD KEY `idx_hotmart_product` (`hotmart_product_id`),
  ADD KEY `idx_user_status_date` (`user_id`,`status`,`purchase_date`),
  ADD KEY `idx_hotmart_product_status` (`hotmart_product_id`,`status`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `access_logs`
--
ALTER TABLE `access_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `materials`
--
ALTER TABLE `materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `product_material_mapping`
--
ALTER TABLE `product_material_mapping`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `upsell_package_materials`
--
ALTER TABLE `upsell_package_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `user_materials`
--
ALTER TABLE `user_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT de tabela `user_notifications`
--
ALTER TABLE `user_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `user_purchases`
--
ALTER TABLE `user_purchases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `access_logs`
--
ALTER TABLE `access_logs`
  ADD CONSTRAINT `access_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `access_logs_ibfk_2` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `product_material_mapping`
--
ALTER TABLE `product_material_mapping`
  ADD CONSTRAINT `product_material_mapping_ibfk_1` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`);

--
-- Restrições para tabelas `upsell_package_materials`
--
ALTER TABLE `upsell_package_materials`
  ADD CONSTRAINT `upsell_package_materials_ibfk_1` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`);

--
-- Restrições para tabelas `user_materials`
--
ALTER TABLE `user_materials`
  ADD CONSTRAINT `user_materials_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_materials_ibfk_2` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `user_notifications`
--
ALTER TABLE `user_notifications`
  ADD CONSTRAINT `user_notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `user_purchases`
--
ALTER TABLE `user_purchases`
  ADD CONSTRAINT `user_purchases_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `user_purchases_ibfk_2` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
