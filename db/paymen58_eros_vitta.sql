-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Tempo de geração: 08/09/2025 às 04:53
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
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `materials`
--

INSERT INTO `materials` (`id`, `titulo`, `tipo`, `caminho`, `descricao`, `criado_em`) VALUES
(1, 'Guia Completo de Marketing Digital', 'ebook', 'ebooks/guia-marketing-digital.html', 'Ebook completo sobre estratégias de marketing digital', '2025-09-06 05:52:09'),
(2, 'Curso de Vendas Online', 'video', 'videos/curso-vendas-online.mp4', 'Vídeo-aulas sobre técnicas de vendas online', '2025-09-06 05:52:09'),
(3, 'Meditação para Iniciantes', 'audio', 'audios/meditacao-iniciantes.mp3', 'Áudio guiado para meditação', '2025-09-06 05:52:09'),
(4, 'Ebook de Receitas Saudáveis', 'ebook', 'ebooks/receitas-saudaveis.html', 'Coleção de receitas nutritivas e deliciosas', '2025-09-06 05:52:09'),
(5, 'Tutorial de Fotografia', 'video', 'videos/tutorial-fotografia.mp4', 'Aprenda técnicas profissionais de fotografia', '2025-09-06 05:52:09'),
(6, '5 Toques Mágicos', 'ebook', 'ebooks/guia-5-toques-magicos.html', 'Um Caminho Simples para Reacender a Intimidade e a Conexão no Dia a Dia', '2025-09-06 15:43:39'),
(7, 'Libido Renovada - O Plano de 21 Dias Para Casais', 'ebook', 'ebooks/libido-renovada.html', 'O Plano de Ação de 21 Dias para Reacender a Intimidade e a Libido', '2025-09-06 15:43:39'),
(8, 'Sem Desejo Nunca Mais', 'ebook', 'ebooks/sem-desejo-nunca-mais.html', 'Descubra como usar sua sensualidade para viver uma paixão que não acaba mais', '2025-09-06 15:43:39'),
(9, 'O Segredo da Resistência', 'ebook', 'ebooks/o-segredo-da-resistencia.html', 'O Guia Para Ele Durar Mais Tempo na Cama', '2025-09-06 15:43:39'),
(10, '5 Toques Mágicos', 'ebook', 'ebooks/guia-5-toques-magicos.html', 'Um Caminho Simples para Reacender a Intimidade e a Conexão no Dia a Dia', '2025-09-06 15:44:29'),
(11, 'Libido Renovada - O Plano de 21 Dias Para Casais', 'ebook', 'ebooks/libido-renovada.html', 'O Plano de Ação de 21 Dias para Reacender a Intimidade e a Libido', '2025-09-06 15:44:29'),
(12, 'Sem Desejo Nunca Mais', 'ebook', 'ebooks/sem-desejo-nunca-mais.html', 'Descubra como usar sua sensualidade para viver uma paixão que não acaba mais', '2025-09-06 15:44:29'),
(13, 'O Segredo da Resistência', 'ebook', 'ebooks/o-segredo-da-resistencia.html', 'O Guia Para Ele Durar Mais Tempo na Cama', '2025-09-06 15:44:29'),
(14, '5 Toques Mágicos', 'ebook', 'ebooks/guia-5-toques-magicos.html', 'Um Caminho Simples para Reacender a Intimidade e a Conexão no Dia a Dia', '2025-09-06 15:45:12'),
(15, 'Libido Renovada - O Plano de 21 Dias Para Casais', 'ebook', 'ebooks/libido-renovada.html', 'O Plano de Ação de 21 Dias para Reacender a Intimidade e a Libido', '2025-09-06 15:45:12'),
(16, 'Sem Desejo Nunca Mais', 'ebook', 'ebooks/sem-desejo-nunca-mais.html', 'Descubra como usar sua sensualidade para viver uma paixão que não acaba mais', '2025-09-06 15:45:12'),
(17, 'O Segredo da Resistência', 'ebook', 'ebooks/o-segredo-da-resistencia.html', 'O Guia Para Ele Durar Mais Tempo na Cama', '2025-09-06 15:45:12');

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
(1, 'E101649402I', 'Libido Renovada: O Plano de 21 Dias para Casais', 1, 'main', '2025-09-08 05:00:08'),
(2, 'F101670521N', 'O Guia Rápido dos 5 Toques Mágicos', 6, 'order_bump', '2025-09-08 05:00:08'),
(3, 'A101789933P', 'Pacote PREMIUM - Libido Renovado', NULL, 'upsell', '2025-09-08 05:00:08'),
(4, 'V101660433I', 'Versão em Áudio: Libido Renovada - O Plano de 21 Dias Para Casais', 7, 'downsell', '2025-09-08 05:00:08'),
(5, 'R101782112U', 'O Segredo da Resistência: O guia prático para durar mais tempo na cama', 9, 'bonus', '2025-09-08 05:00:08'),
(6, 'D101782229U', 'Sem Desejo Nunca Mais! Descubra como usar sua sensualidade para viver uma paixão que não acaba mais', 8, 'bonus', '2025-09-08 05:00:08');

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
(1, 'A101789933P', 1, 'main'),
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
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `users`
--

INSERT INTO `users` (`id`, `email`, `senha`, `nome`, `criado_em`) VALUES
(1, 'lochaydeguerreiro@hotmail.com', '$2y$10$1GPUKgTcIXXCG0FJwlvO.uPoR4w4Ql2dnZLPjOwXzN20.dBoJKFnO', 'Lochayde Guerreiro', '2025-09-08 06:15:30');

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
(32, 1, 1, '2025-09-08 07:50:44'),
(33, 1, 6, '2025-09-08 07:50:44'),
(34, 1, 7, '2025-09-08 07:50:44'),
(35, 1, 9, '2025-09-08 07:50:44'),
(36, 1, 8, '2025-09-08 07:50:44');

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
  `status` enum('active','refunded','cancelled') COLLATE utf8_unicode_ci DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `materials`
--
ALTER TABLE `materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tipo` (`tipo`);

--
-- Índices de tabela `product_material_mapping`
--
ALTER TABLE `product_material_mapping`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `hotmart_product_id` (`hotmart_product_id`),
  ADD KEY `material_id` (`material_id`);

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
-- Índices de tabela `user_purchases`
--
ALTER TABLE `user_purchases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `material_id` (`material_id`),
  ADD KEY `idx_user_purchases` (`user_id`,`status`),
  ADD KEY `idx_hotmart_product` (`hotmart_product_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `materials`
--
ALTER TABLE `materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de tabela `product_material_mapping`
--
ALTER TABLE `product_material_mapping`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
-- AUTO_INCREMENT de tabela `user_purchases`
--
ALTER TABLE `user_purchases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Restrições para tabelas despejadas
--

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
-- Restrições para tabelas `user_purchases`
--
ALTER TABLE `user_purchases`
  ADD CONSTRAINT `user_purchases_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `user_purchases_ibfk_2` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
