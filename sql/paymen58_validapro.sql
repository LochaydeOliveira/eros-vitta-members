-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Tempo de geração: 05/09/2025 às 11:27
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
-- Banco de dados: `paymen58_validapro`
--

DELIMITER $$
--
-- Procedimentos
--
CREATE DEFINER=`paymen58`@`localhost` PROCEDURE `LimparTokensExpirados` ()   BEGIN
    DELETE FROM `recuperacao_senha` 
    WHERE `expira` < NOW() OR `usado` = 1;
END$$

CREATE DEFINER=`paymen58`@`localhost` PROCEDURE `ObterEstatisticasSistema` ()   BEGIN
    SELECT 
        (SELECT COUNT(*) FROM `users` WHERE `active` = 1) as total_usuarios_ativos,
        (SELECT COUNT(*) FROM `results`) as total_analises,
        (SELECT COUNT(*) FROM `results` WHERE `pontos` >= 8) as analises_alto_potencial,
        (SELECT COUNT(*) FROM `results` WHERE `pontos` >= 5 AND `pontos` < 8) as analises_medio_potencial,
        (SELECT COUNT(*) FROM `results` WHERE `pontos` < 5) as analises_baixo_potencial,
        (SELECT AVG(`pontos`) FROM `results`) as media_pontuacao;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `leads_previas`
--

CREATE TABLE `leads_previas` (
  `id` int(11) NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `leads_previas`
--

INSERT INTO `leads_previas` (`id`, `email`, `source`, `user_agent`, `ip`, `created_at`) VALUES
(1, 'lochaydeguerreiro@hotmail.com', 'lp-libido-renovado', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '179.48.2.42', '2025-08-29 18:38:14');

-- --------------------------------------------------------

--
-- Estrutura para tabela `recuperacao_senha`
--

CREATE TABLE `recuperacao_senha` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expira` datetime NOT NULL,
  `usado` tinyint(1) DEFAULT '0',
  `usado_em` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `recuperacao_senha`
--

INSERT INTO `recuperacao_senha` (`id`, `user_id`, `token`, `expira`, `usado`, `usado_em`, `created_at`) VALUES
(32, 2, '9871142882797fa18ba7d1518f792048b0094de7818eb0523237288662debbcb', '2025-07-24 00:24:00', 1, '2025-07-23 23:25:19', '2025-07-24 02:24:00'),
(33, 2, '481a1b5b7ef503363a0e2cedcc1afeac082f7fd1737804d8da167bd7396dc9fd', '2025-07-24 01:20:58', 1, '2025-07-24 00:22:04', '2025-07-24 03:20:58'),
(34, 17, '5be404f5f2ffaf4f980d72300e9c0c49ebdee9dc8a935f34b55bc88cae88a6b1', '2025-08-29 18:33:23', 1, '2025-08-29 17:34:27', '2025-08-29 20:33:23');

-- --------------------------------------------------------

--
-- Estrutura para tabela `results`
--

CREATE TABLE `results` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `promessa_principal` text COLLATE utf8mb4_unicode_ci,
  `cliente_consciente` text COLLATE utf8mb4_unicode_ci,
  `beneficios` text COLLATE utf8mb4_unicode_ci,
  `mecanismo_unico` text COLLATE utf8mb4_unicode_ci,
  `pontos` int(11) DEFAULT '0',
  `nota_final` int(11) DEFAULT '0',
  `mensagem` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `whatsapp` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usuario` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'Cliente',
  `plano` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'Basic',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `active` tinyint(1) DEFAULT '1',
  `last_login` datetime DEFAULT NULL,
  `reset_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reset_token_expira` datetime DEFAULT NULL,
  `observacoes` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `name`, `whatsapp`, `usuario`, `plano`, `created_at`, `active`, `last_login`, `reset_token`, `reset_token_expira`, `observacoes`) VALUES
(2, 'lochaydeguerreiro@hotmail.com', '$2y$10$JHCY9r04LDahibeccBQO/.hXhPZDWapqaxZGE3caWz.RHJ6xnEfEa', 'Lochayde', '(85) 92144-7153', 'Administrador', 'Basic', '2025-07-17 10:11:59', 1, '2025-07-26 01:25:01', '1f9b6a11f3fa0c45ac99bbcdbbd632eb95a7caed00c5c7f808b4571ffe324304', '2025-07-18 12:50:56', ''),
(3, 'admin@exemplo.com', '$2y$10$uR1Pho3bWV1azB3FdlYa7uWM925bGwe.PYvtMj52FRnqdnETbX8My', 'Administrador', NULL, 'Cliente', 'Basic', '2025-07-17 22:29:59', 1, '2025-07-23 21:19:46', NULL, NULL, NULL),
(4, 'admin@validapro.com', '$2y$10$lJzY6RlNxY5BJsmNtA6zS.2KK17J7O7SIfWebsCNhg3uKjRIJwe.i', 'Administrador ValidaPro', NULL, 'admin', 'Basic', '2025-07-18 00:27:04', 1, '2025-07-17 22:24:02', NULL, NULL, NULL),
(7, 'veramdssoares@gmail.com', '$2y$10$kVAbq1rfEjuW7cXRfJCwGuM.i2o8tvE0n.AgqpoOmP/WXFGl5dR3y', 'Vera Soares', '(51) 99955-7204', 'Cliente', 'Basic', '2025-07-23 19:34:39', 0, '2025-07-25 10:20:34', NULL, NULL, ''),
(16, 'brasilhilariooficial@gmail.com', '$2y$10$fVYllpLfks/Az8eyOU8h6.bDY/IpISBg/bfstFlKAPfvZUcU3L/BC', 'Hilario Brasil', '(61) 9421-6467', 'Cliente', 'Basic', '2025-07-24 10:21:39', 1, '2025-07-24 14:25:32', NULL, NULL, ''),
(17, 'lochaydeguerreiro2@gmail.com', '$2y$10$9KS7N9Z5453EaprZKVtMmu25zqwNk2wYrcWdiZ/DOpec75jkyL98q', 'Lochayde Cliente', '(85) 92144-7153', 'Cliente', 'Basic', '2025-07-24 21:04:56', 1, '2025-09-05 00:25:56', NULL, NULL, '');

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `vw_resultados_detalhados`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `vw_resultados_detalhados` (
`id` int(11)
,`user_id` int(11)
,`nome_usuario` varchar(255)
,`email_usuario` varchar(255)
,`promessa_principal` text
,`cliente_consciente` text
,`beneficios` text
,`mecanismo_unico` text
,`pontos` int(11)
,`nota_final` int(11)
,`mensagem` varchar(255)
,`created_at` timestamp
,`categoria_potencial` varchar(15)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `vw_usuarios_analises`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `vw_usuarios_analises` (
`id` int(11)
,`email` varchar(255)
,`name` varchar(255)
,`usuario` varchar(20)
,`active` tinyint(1)
,`last_login` datetime
,`total_analises` bigint(21)
,`ultima_analise` timestamp
,`media_pontuacao` decimal(14,4)
);

-- --------------------------------------------------------

--
-- Estrutura para view `vw_resultados_detalhados`
--
DROP TABLE IF EXISTS `vw_resultados_detalhados`;

CREATE ALGORITHM=UNDEFINED DEFINER=`paymen58`@`localhost` SQL SECURITY DEFINER VIEW `vw_resultados_detalhados`  AS SELECT `r`.`id` AS `id`, `r`.`user_id` AS `user_id`, `u`.`name` AS `nome_usuario`, `u`.`email` AS `email_usuario`, `r`.`promessa_principal` AS `promessa_principal`, `r`.`cliente_consciente` AS `cliente_consciente`, `r`.`beneficios` AS `beneficios`, `r`.`mecanismo_unico` AS `mecanismo_unico`, `r`.`pontos` AS `pontos`, `r`.`nota_final` AS `nota_final`, `r`.`mensagem` AS `mensagem`, `r`.`created_at` AS `created_at`, (case when (`r`.`pontos` >= 8) then 'Alto Potencial' when (`r`.`pontos` >= 5) then 'Médio Potencial' else 'Baixo Potencial' end) AS `categoria_potencial` FROM (`results` `r` join `users` `u` on((`r`.`user_id` = `u`.`id`))) ;

-- --------------------------------------------------------

--
-- Estrutura para view `vw_usuarios_analises`
--
DROP TABLE IF EXISTS `vw_usuarios_analises`;

CREATE ALGORITHM=UNDEFINED DEFINER=`paymen58`@`localhost` SQL SECURITY DEFINER VIEW `vw_usuarios_analises`  AS SELECT `u`.`id` AS `id`, `u`.`email` AS `email`, `u`.`name` AS `name`, `u`.`usuario` AS `usuario`, `u`.`active` AS `active`, `u`.`last_login` AS `last_login`, count(`r`.`id`) AS `total_analises`, max(`r`.`created_at`) AS `ultima_analise`, avg(`r`.`pontos`) AS `media_pontuacao` FROM (`users` `u` left join `results` `r` on((`u`.`id` = `r`.`user_id`))) GROUP BY `u`.`id`, `u`.`email`, `u`.`name`, `u`.`usuario`, `u`.`active`, `u`.`last_login` ;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `leads_previas`
--
ALTER TABLE `leads_previas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`);

--
-- Índices de tabela `recuperacao_senha`
--
ALTER TABLE `recuperacao_senha`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_expira` (`expira`);

--
-- Índices de tabela `results`
--
ALTER TABLE `results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_results_user_created` (`user_id`,`created_at`),
  ADD KEY `idx_results_pontos` (`pontos`);

--
-- Índices de tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_email_active` (`email`,`active`),
  ADD KEY `idx_users_last_login` (`last_login`),
  ADD KEY `idx_users_created_at` (`created_at`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `leads_previas`
--
ALTER TABLE `leads_previas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `recuperacao_senha`
--
ALTER TABLE `recuperacao_senha`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT de tabela `results`
--
ALTER TABLE `results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `recuperacao_senha`
--
ALTER TABLE `recuperacao_senha`
  ADD CONSTRAINT `recuperacao_senha_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `results`
--
ALTER TABLE `results`
  ADD CONSTRAINT `results_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
