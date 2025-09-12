-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Tempo de geração: 12/09/2025 às 13:27
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
-- Banco de dados: `paymen58_eros_vitta_members`
--

DELIMITER $$
--
-- Procedimentos
--
CREATE DEFINER=`paymen58`@`localhost` PROCEDURE `sp_run_daily_snapshots` (IN `dia_ref` DATE)   BEGIN
  CALL sp_snapshot_resumo_diario(dia_ref);
  CALL sp_snapshot_faturamento_diario(dia_ref);
  CALL sp_snapshot_top_produtos_diario(dia_ref);
END$$

CREATE DEFINER=`paymen58`@`localhost` PROCEDURE `sp_snapshot_faturamento_diario` (IN `dia_ref` DATE)   BEGIN
  INSERT INTO snapshot_faturamento_diario (dia, vendas, faturamento)
  SELECT
    dia_ref AS dia,
    COUNT(*) AS vendas,
    COALESCE(SUM(valor_pago), 0.00) AS faturamento
  FROM compras
  WHERE status = 'aprovada'
    AND DATE(data_confirmacao) = dia_ref
  ON DUPLICATE KEY UPDATE
    vendas = VALUES(vendas),
    faturamento = VALUES(faturamento),
    criado_em = CURRENT_TIMESTAMP;
END$$

CREATE DEFINER=`paymen58`@`localhost` PROCEDURE `sp_snapshot_resumo_diario` (IN `dia_ref` DATE)   BEGIN
  INSERT INTO snapshot_resumo_diario (
    dia,
    usuarios_total,
    usuarios_ativos,
    produtos_ativos,
    vendas_confirmadas_total,
    faturamento_confirmado_total,
    vendas_confirmadas_no_dia,
    faturamento_confirmado_no_dia
  )
  VALUES (
    dia_ref,
    (SELECT COUNT(*) FROM usuarios),
    (SELECT COUNT(*) FROM usuarios WHERE status = 'ativo'),
    (SELECT COUNT(*) FROM produtos WHERE ativo = 1),
    (SELECT COUNT(*) FROM compras WHERE status = 'aprovada'),
    (SELECT COALESCE(SUM(valor_pago), 0.00) FROM compras WHERE status = 'aprovada'),
    (SELECT COUNT(*) FROM compras WHERE status = 'aprovada' AND DATE(data_confirmacao) = dia_ref),
    (SELECT COALESCE(SUM(valor_pago), 0.00) FROM compras WHERE status = 'aprovada' AND DATE(data_confirmacao) = dia_ref)
  )
  ON DUPLICATE KEY UPDATE
    usuarios_total = VALUES(usuarios_total),
    usuarios_ativos = VALUES(usuarios_ativos),
    produtos_ativos = VALUES(produtos_ativos),
    vendas_confirmadas_total = VALUES(vendas_confirmadas_total),
    faturamento_confirmado_total = VALUES(faturamento_confirmado_total),
    vendas_confirmadas_no_dia = VALUES(vendas_confirmadas_no_dia),
    faturamento_confirmado_no_dia = VALUES(faturamento_confirmado_no_dia),
    criado_em = CURRENT_TIMESTAMP;
END$$

CREATE DEFINER=`paymen58`@`localhost` PROCEDURE `sp_snapshot_top_produtos_diario` (IN `dia_ref` DATE)   BEGIN
  -- Recalcula o dia inteiro de forma idempotente
  DELETE FROM snapshot_top_produtos_diario WHERE dia = dia_ref;

  INSERT INTO snapshot_top_produtos_diario (dia, produto_id, vendas, faturamento)
  SELECT
    dia_ref AS dia,
    c.produto_id,
    COUNT(*) AS vendas,
    COALESCE(SUM(c.valor_pago), 0.00) AS faturamento
  FROM compras c
  WHERE c.status = 'aprovada'
    AND DATE(c.data_confirmacao) = dia_ref
  GROUP BY c.produto_id;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `acessos`
--

CREATE TABLE `acessos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `usuario_id` bigint(20) UNSIGNED NOT NULL,
  `produto_id` bigint(20) UNSIGNED NOT NULL,
  `compra_id` bigint(20) UNSIGNED DEFAULT NULL,
  `origem` enum('hotmart','manual') NOT NULL DEFAULT 'hotmart',
  `status` enum('ativo','bloqueado') NOT NULL DEFAULT 'ativo',
  `data_liberacao` datetime DEFAULT NULL,
  `data_bloqueio` datetime DEFAULT NULL,
  `motivo_bloqueio` varchar(255) DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `liberacao_email_enviado_em` datetime DEFAULT NULL,
  `liberacao_email_status` enum('pendente','sucesso','falha') NOT NULL DEFAULT 'pendente',
  `liberacao_email_tentativas` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `liberacao_email_ultima_tentativa_em` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Despejando dados para a tabela `acessos`
--

INSERT INTO `acessos` (`id`, `usuario_id`, `produto_id`, `compra_id`, `origem`, `status`, `data_liberacao`, `data_bloqueio`, `motivo_bloqueio`, `criado_em`, `atualizado_em`, `liberacao_email_enviado_em`, `liberacao_email_status`, `liberacao_email_tentativas`, `liberacao_email_ultima_tentativa_em`) VALUES
(1, 1, 1, 1, 'manual', 'ativo', '2025-09-16 20:30:00', NULL, NULL, '2025-09-09 19:24:39', '2025-09-09 20:54:01', NULL, 'pendente', 0, NULL),
(2, 2, 1, 1, 'manual', 'bloqueado', '2025-09-11 17:01:33', '2025-09-11 21:00:15', 'manual', '2025-09-09 20:55:18', '2025-09-11 21:00:15', NULL, 'pendente', 0, NULL),
(3, 2, 2, NULL, 'manual', 'ativo', '2025-09-11 12:25:24', NULL, NULL, '2025-09-10 11:03:35', '2025-09-11 19:06:20', '2025-09-10 23:39:05', 'sucesso', 1, '2025-09-10 23:39:05'),
(10, 4, 1, NULL, 'manual', 'ativo', '2025-09-12 13:18:23', NULL, NULL, '2025-09-12 13:18:23', '2025-09-12 13:18:23', NULL, 'pendente', 0, NULL),
(11, 4, 2, NULL, 'manual', 'ativo', '2025-09-12 13:18:27', NULL, NULL, '2025-09-12 13:18:27', '2025-09-12 13:18:27', NULL, 'pendente', 0, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `admins`
--

CREATE TABLE `admins` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nome` varchar(150) NOT NULL,
  `email` varchar(190) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `is_superadmin` tinyint(1) NOT NULL DEFAULT '0',
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Despejando dados para a tabela `admins`
--

INSERT INTO `admins` (`id`, `nome`, `email`, `senha_hash`, `is_superadmin`, `ativo`, `criado_em`, `atualizado_em`) VALUES
(2, 'Administrador', 'lochaydeguerreiro@hotmail.com', '$2y$10$5p1diCc328fd4bGYEk/pMOeGzyW5AHHRDhvL3tTrEXRiN9iFePvre', 1, 1, '2025-09-09 14:08:25', '2025-09-09 17:30:38'),
(3, 'Admin Backup', 'admin.backup@erosvitta.com.br', '$2y$10$UlRSUIcRcYlX1GDrorgoLOeVJdU52H5BgTPM4sJYQjrskrTCTo7rO', 1, 1, '2025-09-09 17:43:11', '2025-09-09 17:43:11');

-- --------------------------------------------------------

--
-- Estrutura para tabela `admin_password_resets`
--

CREATE TABLE `admin_password_resets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `admin_id` bigint(20) UNSIGNED NOT NULL,
  `token` varchar(128) NOT NULL,
  `expira_em` datetime NOT NULL,
  `usado_em` datetime DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estrutura para tabela `compras`
--

CREATE TABLE `compras` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `usuario_id` bigint(20) UNSIGNED NOT NULL,
  `produto_id` bigint(20) UNSIGNED NOT NULL,
  `origem` enum('hotmart','manual') NOT NULL DEFAULT 'hotmart',
  `status` enum('pendente','aprovada','cancelada','estornada') NOT NULL DEFAULT 'pendente',
  `hotmart_transaction_id` varchar(120) DEFAULT NULL,
  `valor_pago` decimal(10,2) DEFAULT NULL,
  `moeda` char(3) DEFAULT NULL,
  `data_compra` datetime DEFAULT NULL,
  `data_confirmacao` datetime DEFAULT NULL,
  `data_liberacao` datetime DEFAULT NULL,
  `observacoes` text,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Despejando dados para a tabela `compras`
--

INSERT INTO `compras` (`id`, `usuario_id`, `produto_id`, `origem`, `status`, `hotmart_transaction_id`, `valor_pago`, `moeda`, `data_compra`, `data_confirmacao`, `data_liberacao`, `observacoes`, `criado_em`, `atualizado_em`) VALUES
(1, 2, 1, 'hotmart', 'aprovada', 'TX1', 99.90, 'BRL', '2025-09-09 20:30:00', '2025-09-09 20:30:00', '2025-09-16 20:30:00', NULL, '2025-09-09 20:54:01', '2025-09-09 20:55:18');

-- --------------------------------------------------------

--
-- Estrutura para tabela `download_tokens`
--

CREATE TABLE `download_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `usuario_id` bigint(20) UNSIGNED NOT NULL,
  `produto_id` bigint(20) UNSIGNED NOT NULL,
  `token` varchar(128) NOT NULL,
  `expira_em` datetime NOT NULL,
  `usado_em` datetime DEFAULT NULL,
  `ip_geracao` varchar(45) DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Despejando dados para a tabela `download_tokens`
--

INSERT INTO `download_tokens` (`id`, `usuario_id`, `produto_id`, `token`, `expira_em`, `usado_em`, `ip_geracao`, `criado_em`) VALUES
(1, 1, 1, '0cb7484f6d8cf6c455ab843303f0ab6858d50a7220beb1ebb3c9c588fbfefbbc', '2025-09-09 20:18:00', '2025-09-09 20:03:55', NULL, '2025-09-09 20:03:00'),
(2, 1, 1, '196f1b7654003f4eb45f7b285538e61b77e9a06eee135f6f13a2292094b81e22', '2025-09-09 20:25:01', NULL, NULL, '2025-09-09 20:10:01'),
(3, 1, 1, '1a74c7118cf28b9a4ae0d3f7f2c871d012059f90e3e8930580b6313ac5a12fa8', '2025-09-09 20:26:46', '2025-09-09 20:12:07', NULL, '2025-09-09 20:11:46'),
(4, 2, 2, 'b0fc350565353aa53b629d55a5f74515a57acf2744cd125449400a2f6388295a', '2025-09-10 11:54:23', '2025-09-10 11:39:23', NULL, '2025-09-10 11:39:23');

-- --------------------------------------------------------

--
-- Estrutura para tabela `password_resets`
--

CREATE TABLE `password_resets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `usuario_id` bigint(20) UNSIGNED NOT NULL,
  `token` varchar(128) NOT NULL,
  `expira_em` datetime NOT NULL,
  `usado_em` datetime DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Despejando dados para a tabela `password_resets`
--

INSERT INTO `password_resets` (`id`, `usuario_id`, `token`, `expira_em`, `usado_em`, `criado_em`) VALUES
(1, 2, '66dc7cf864524c47181b487cb15fa82e17a38713bd450b59352cd7e440dad32d', '2025-09-11 02:06:56', NULL, '2025-09-11 01:06:56'),
(2, 2, '727b2aa970fb329f5e73fc96793f271e70ed335c167d0befeb4ec437ce332579', '2025-09-11 02:11:49', NULL, '2025-09-11 01:11:49'),
(3, 2, 'ef7936295501c9047648fb17bd466bba3cb35191a5364def4bbf4f1fe02d6b65', '2025-09-11 02:16:36', '2025-09-11 01:17:28', '2025-09-11 01:16:36'),
(4, 2, '37c2121b9a17d12d48a404ab818334e8331e93e0a70c32a59e719cfe4d77c961', '2025-09-11 19:40:33', '2025-09-11 18:41:42', '2025-09-11 18:40:33');

-- --------------------------------------------------------

--
-- Estrutura para tabela `produtos`
--

CREATE TABLE `produtos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tipo` enum('ebook','audio') NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `descricao` text,
  `capa_url` varchar(500) DEFAULT NULL,
  `storage_path_pdf` varchar(500) DEFAULT NULL,
  `storage_path_audio` varchar(500) DEFAULT NULL,
  `storage_view_pdf` varchar(500) DEFAULT NULL,
  `storage_dl_pdf` varchar(500) DEFAULT NULL,
  `storage_view_audio` varchar(500) DEFAULT NULL,
  `storage_dl_audio` varchar(500) DEFAULT NULL,
  `duracao_segundos` int(10) UNSIGNED DEFAULT NULL,
  `aplicar_watermark` tinyint(1) NOT NULL DEFAULT '0',
  `hotmart_product_id` varchar(100) DEFAULT NULL,
  `checkout_url` varchar(500) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `storage_view_audio_dir` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Despejando dados para a tabela `produtos`
--

INSERT INTO `produtos` (`id`, `tipo`, `titulo`, `slug`, `descricao`, `capa_url`, `storage_path_pdf`, `storage_path_audio`, `storage_view_pdf`, `storage_dl_pdf`, `storage_view_audio`, `storage_dl_audio`, `duracao_segundos`, `aplicar_watermark`, `hotmart_product_id`, `checkout_url`, `ativo`, `criado_em`, `atualizado_em`, `storage_view_audio_dir`) VALUES
(1, 'ebook', 'O Segredo da Resistência - O Guia Prático Para Ele Durar Mais na Cama', 'o-segredo-da-resistencia-o-guia-pratico-para-ele-durar-mais-na-cama', NULL, 'https://erosvitta.com.br/covers/o-segredo-da-resistencia.webp', '/home1/paymen58/storage/ebooks/o-segredo-da-resistencia-o-guia-pratico-para-urar-mais-tempo-na-cama.pdf', NULL, '/home1/paymen58/storage/ebooks/view/o-segredo-da-resistencia.pdf', '/home1/paymen58/storage/ebooks/download/o-segredo-da-resistencia-protegido001.pdf', NULL, NULL, NULL, 0, NULL, 'https://pay.hotmart.com/R101782112U', 1, '2025-09-09 18:13:17', '2025-09-11 12:49:22', NULL),
(2, 'audio', 'Libido Renovada (Versão em Áudio)', 'versao-em-audio-libido-renovada', NULL, 'https://erosvitta.com.br/covers/capa-libido-renovado-versao-em-audio.webp', NULL, NULL, NULL, NULL, 'storage/audios/view/versao-em-audio-libido-renovada/', NULL, NULL, 0, NULL, NULL, 1, '2025-09-09 23:24:57', '2025-09-11 12:23:24', '/home1/paymen58/storage/audios/view/versao-em-audio-libido-renovada/');

-- --------------------------------------------------------

--
-- Estrutura para tabela `snapshot_faturamento_diario`
--

CREATE TABLE `snapshot_faturamento_diario` (
  `dia` date NOT NULL,
  `vendas` int(10) UNSIGNED NOT NULL,
  `faturamento` decimal(12,2) NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estrutura para tabela `snapshot_resumo_diario`
--

CREATE TABLE `snapshot_resumo_diario` (
  `dia` date NOT NULL,
  `usuarios_total` int(10) UNSIGNED NOT NULL,
  `usuarios_ativos` int(10) UNSIGNED NOT NULL,
  `produtos_ativos` int(10) UNSIGNED NOT NULL,
  `vendas_confirmadas_total` int(10) UNSIGNED NOT NULL,
  `faturamento_confirmado_total` decimal(12,2) NOT NULL,
  `vendas_confirmadas_no_dia` int(10) UNSIGNED NOT NULL,
  `faturamento_confirmado_no_dia` decimal(12,2) NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estrutura para tabela `snapshot_top_produtos_diario`
--

CREATE TABLE `snapshot_top_produtos_diario` (
  `dia` date NOT NULL,
  `produto_id` bigint(20) UNSIGNED NOT NULL,
  `vendas` int(10) UNSIGNED NOT NULL,
  `faturamento` decimal(12,2) NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nome` varchar(150) NOT NULL,
  `email` varchar(190) NOT NULL,
  `senha_hash` varchar(255) DEFAULT NULL,
  `status` enum('ativo','bloqueado') NOT NULL DEFAULT 'ativo',
  `email_verificado_em` datetime DEFAULT NULL,
  `hotmart_user_id` varchar(100) DEFAULT NULL,
  `ultimo_login_em` datetime DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha_hash`, `status`, `email_verificado_em`, `hotmart_user_id`, `ultimo_login_em`, `criado_em`, `atualizado_em`) VALUES
(1, 'João Paulo', 'usuario.teste+evm@exemplo.com', '$2y$10$19toMuLB3Pk5YSYWL1OB5.EZ3xrNN8oPgz9EOn6MScTK9aL.t9j5e', 'ativo', NULL, 'U123', NULL, '2025-09-09 18:46:30', '2025-09-11 18:15:38'),
(2, 'Lochayde Oliveira', 'lochaydeguerreiro@hotmail.com', '$2y$10$2RIf3CdiEGAXT6hQwaSmXuGF0s/vArUgTi29V8wzRDUnl/0lxWOEO', 'ativo', NULL, 'U123', '2025-09-12 13:14:08', '2025-09-09 20:55:18', '2025-09-12 13:14:08'),
(4, 'Lochayde Oliveira', 'lochaydeguerreiro2@gmail.com', '$2y$10$2RIf3CdiEGAXT6hQwaSmXuGF0s/vArUgTi29V8wzRDUnl/0lxWOEO', 'ativo', NULL, 'U123', '2025-09-12 13:17:02', '2025-09-09 20:55:18', '2025-09-12 13:17:02');

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_acessos_ativos`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_acessos_ativos` (
`acesso_id` bigint(20) unsigned
,`usuario_id` bigint(20) unsigned
,`usuario_nome` varchar(150)
,`usuario_email` varchar(190)
,`produto_id` bigint(20) unsigned
,`produto_titulo` varchar(200)
,`produto_tipo` enum('ebook','audio')
,`origem` enum('hotmart','manual')
,`data_liberacao` datetime
,`acesso_criado_em` datetime
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_dashboard_resumo`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_dashboard_resumo` (
`usuarios_total` bigint(21)
,`usuarios_ativos` bigint(21)
,`produtos_total` bigint(21)
,`produtos_ativos` bigint(21)
,`vendas_confirmadas_total` bigint(21)
,`faturamento_total_confirmado` decimal(32,2)
,`vendas_hoje` bigint(21)
,`faturamento_hoje` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_downloads_pendentes`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_downloads_pendentes` (
`compra_id` bigint(20) unsigned
,`usuario_id` bigint(20) unsigned
,`usuario_nome` varchar(150)
,`produto_id` bigint(20) unsigned
,`produto_titulo` varchar(200)
,`data_confirmacao` datetime
,`data_liberacao` datetime
,`dias_para_liberar` bigint(21)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_faturamento_diario`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_faturamento_diario` (
`dia` date
,`vendas` bigint(21)
,`faturamento` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_faturamento_mensal`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_faturamento_mensal` (
`mes` varchar(10)
,`vendas` bigint(21)
,`faturamento` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_produtos_ativos`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_produtos_ativos` (
`produto_id` bigint(20) unsigned
,`titulo` varchar(200)
,`tipo` enum('ebook','audio')
,`ativo` tinyint(1)
,`vendas_confirmadas` bigint(21)
,`acessos_ativos` bigint(21)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_top_produtos`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_top_produtos` (
`produto_id` bigint(20) unsigned
,`titulo` varchar(200)
,`tipo` enum('ebook','audio')
,`vendas_confirmadas` bigint(21)
,`faturamento_confirmado` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_usuarios_compras`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_usuarios_compras` (
`usuario_id` bigint(20) unsigned
,`nome` varchar(150)
,`email` varchar(190)
,`status` enum('ativo','bloqueado')
,`usuario_criado_em` datetime
,`total_compras` bigint(21)
,`total_confirmadas` decimal(23,0)
,`total_gasto_confirmado` decimal(32,2)
,`ultima_compra_confirmada_em` datetime
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_vendas_confirmadas`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_vendas_confirmadas` (
`compra_id` bigint(20) unsigned
,`hotmart_transaction_id` varchar(120)
,`usuario_id` bigint(20) unsigned
,`usuario_nome` varchar(150)
,`usuario_email` varchar(190)
,`produto_id` bigint(20) unsigned
,`produto_titulo` varchar(200)
,`produto_tipo` enum('ebook','audio')
,`valor_pago` decimal(10,2)
,`moeda` char(3)
,`data_compra` datetime
,`data_confirmacao` datetime
,`data_liberacao` datetime
,`download_liberado` int(1)
);

-- --------------------------------------------------------

--
-- Estrutura para tabela `webhook_eventos`
--

CREATE TABLE `webhook_eventos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `origem` varchar(50) NOT NULL DEFAULT 'hotmart',
  `evento_tipo` varchar(120) DEFAULT NULL,
  `assinatura` varchar(255) DEFAULT NULL,
  `payload` longtext,
  `headers` longtext,
  `processado_em` datetime DEFAULT NULL,
  `resultado_status` enum('sucesso','falha','ignorado') DEFAULT NULL,
  `erro_mensagem` text,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Despejando dados para a tabela `webhook_eventos`
--

INSERT INTO `webhook_eventos` (`id`, `origem`, `evento_tipo`, `assinatura`, `payload`, `headers`, `processado_em`, `resultado_status`, `erro_mensagem`, `criado_em`) VALUES
(2, 'hotmart', 'approved', 'JXw991Om7EB7yY0Xf8ptOB4FJMlQaP534873', '{\"event\":\"approved\",\"buyer\":{\"email\":\"usuario.teste+evm@exemplo.com\",\"name\":\"Cliente Teste\",\"ucode\":\"U123\"},\"product\":{\"id\":\"P001\"},\"purchase\":{\"transaction\":\"TX1\",\"status\":\"approved\",\"price\":99.900000000000005684341886080801486968994140625,\"currency\":\"BRL\",\"approved_date\":\"2025-09-09T20:30:00-03:00\"}}', '{\"accept\":\"*\\/*\",\"host\":\"erosvitta.com.br\",\"user-agent\":\"curl\\/8.10.1\",\"x-hotmart-hottok\":\"JXw991Om7EB7yY0Xf8ptOB4FJMlQaP534873\",\"x-https\":\"1\"}', '2025-09-09 20:49:42', 'falha', 'Produto não cadastrado para hotmart_product_id=P001', '2025-09-09 20:49:42'),
(3, 'hotmart', 'approved', 'JXw991Om7EB7yY0Xf8ptOB4FJMlQaP534873', '{\"event\":\"approved\",\"buyer\":{\"email\":\"usuario.teste+evm@exemplo.com\",\"name\":\"Cliente Teste\",\"ucode\":\"U123\"},\"product\":{\"id\":\"P001\"},\"purchase\":{\"transaction\":\"TX1\",\"status\":\"approved\",\"price\":99.900000000000005684341886080801486968994140625,\"currency\":\"BRL\",\"approved_date\":\"2025-09-09T20:30:00-03:00\"}}', '{\"accept\":\"*\\/*\",\"host\":\"erosvitta.com.br\",\"user-agent\":\"curl\\/8.10.1\",\"x-hotmart-hottok\":\"JXw991Om7EB7yY0Xf8ptOB4FJMlQaP534873\",\"x-https\":\"1\"}', '2025-09-09 20:50:03', 'falha', 'Produto não cadastrado para hotmart_product_id=P001', '2025-09-09 20:50:03'),
(4, 'hotmart', 'approved', 'JXw991Om7EB7yY0Xf8ptOB4FJMlQaP534873', '{\"event\":\"approved\",\"buyer\":{\"email\":\"usuario.teste+evm@exemplo.com\",\"name\":\"Cliente Teste\",\"ucode\":\"U123\"},\"product\":{\"id\":\"6157971\"},\"purchase\":{\"transaction\":\"TX1\",\"status\":\"approved\",\"price\":99.900000000000005684341886080801486968994140625,\"currency\":\"BRL\",\"approved_date\":\"2025-09-09T20:30:00-03:00\"}}', '{\"accept\":\"*\\/*\",\"host\":\"erosvitta.com.br\",\"user-agent\":\"curl\\/8.10.1\",\"x-hotmart-hottok\":\"JXw991Om7EB7yY0Xf8ptOB4FJMlQaP534873\",\"x-https\":\"1\"}', '2025-09-09 20:54:01', 'sucesso', NULL, '2025-09-09 20:54:01'),
(5, 'hotmart', 'approved', 'JXw991Om7EB7yY0Xf8ptOB4FJMlQaP534873', '{\"event\":\"approved\",\"buyer\":{\"email\":\"lochaydeguerreiro@hotmail.com\",\"name\":\"Cliente Teste\",\"ucode\":\"U123\"},\"product\":{\"id\":\"6157971\"},\"purchase\":{\"transaction\":\"TX1\",\"status\":\"approved\",\"price\":99.900000000000005684341886080801486968994140625,\"currency\":\"BRL\",\"approved_date\":\"2025-09-09T20:30:00-03:00\"}}', '{\"accept\":\"*\\/*\",\"host\":\"erosvitta.com.br\",\"user-agent\":\"curl\\/8.10.1\",\"x-hotmart-hottok\":\"JXw991Om7EB7yY0Xf8ptOB4FJMlQaP534873\",\"x-https\":\"1\"}', '2025-09-09 20:55:18', 'sucesso', NULL, '2025-09-09 20:55:18'),
(6, 'hotmart', 'hotmart', 'JXw991Om7EB7yY0Xf8ptOB4FJMlQaP534873', '[]', '{\"connection\":\"Keep-Alive\",\"host\":\"erosvitta.com.br\",\"user-agent\":\"Mozilla\\/5.0 (Windows NT; Windows NT 10.0; pt-BR) WindowsPowerShell\\/5.1.19041.6328\",\"x-hotmart-hottok\":\"JXw991Om7EB7yY0Xf8ptOB4FJMlQaP534873\",\"x-https\":\"1\"}', '2025-09-12 12:29:01', 'falha', 'Payload inválido: falta email ou product_id', '2025-09-12 12:29:01'),
(7, 'hotmart', 'hotmart', 'JXw991Om7EB7yY0Xf8ptOB4FJMlQaP534873', '[]', '{\"host\":\"erosvitta.com.br\",\"user-agent\":\"Mozilla\\/5.0 (Windows NT; Windows NT 10.0; pt-BR) WindowsPowerShell\\/5.1.19041.6328\",\"x-hotmart-hottok\":\"JXw991Om7EB7yY0Xf8ptOB4FJMlQaP534873\",\"x-https\":\"1\"}', '2025-09-12 12:30:10', 'falha', 'Payload inválido: falta email ou product_id', '2025-09-12 12:30:10'),
(8, 'hotmart', 'hotmart', 'JXw991Om7EB7yY0Xf8ptOB4FJMlQaP534873', '[]', '{\"host\":\"erosvitta.com.br\",\"user-agent\":\"Mozilla\\/5.0 (Windows NT; Windows NT 10.0; pt-BR) WindowsPowerShell\\/5.1.19041.6328\",\"x-hotmart-hottok\":\"JXw991Om7EB7yY0Xf8ptOB4FJMlQaP534873\",\"x-https\":\"1\"}', '2025-09-12 12:31:33', 'falha', 'Payload inválido: falta email ou product_id', '2025-09-12 12:31:33'),
(9, 'hotmart', 'hotmart', 'JXw991Om7EB7yY0Xf8ptOB4FJMlQaP534873', '[]', '{\"host\":\"erosvitta.com.br\",\"user-agent\":\"Mozilla\\/5.0 (Windows NT; Windows NT 10.0; pt-BR) WindowsPowerShell\\/5.1.19041.6328\",\"x-hotmart-hottok\":\"JXw991Om7EB7yY0Xf8ptOB4FJMlQaP534873\",\"x-https\":\"1\"}', '2025-09-12 12:32:33', 'falha', 'Payload inválido: falta email ou product_id', '2025-09-12 12:32:33'),
(10, 'hotmart', 'PURCHASE_APPROVED', 'JXw991Om7EB7yY0Xf8ptOB4FJMlQaP534873', '{\"data\":{\"product\":{\"support_email\":\"support@hotmart.com.br\",\"has_co_production\":false,\"name\":\"Produto test postback2\",\"warranty_date\":\"2017-12-27T00:00:00Z\",\"is_physical_product\":false,\"id\":0,\"ucode\":\"fb056612-bcc6-4217-9e6d-2a5d1110ac2f\",\"content\":{\"has_physical_products\":true,\"products\":[{\"name\":\"How to Make Clear Ice\",\"is_physical_product\":false,\"id\":4774438,\"ucode\":\"559fef42-3406-4d82-b775-d09bd33936b1\"},{\"name\":\"Organizador de Poeira\",\"is_physical_product\":true,\"id\":4999597,\"ucode\":\"099e7644-b7d1-43d6-82a9-ec6be0118a4b\"}]}},\"commissions\":[{\"currency_value\":\"BRL\",\"source\":\"MARKETPLACE\",\"value\":149.5},{\"currency_value\":\"BRL\",\"source\":\"PRODUCER\",\"value\":1350.5}],\"purchase\":{\"original_offer_price\":{\"currency_value\":\"BRL\",\"value\":1500},\"checkout_country\":{\"iso\":\"BR\",\"name\":\"Brasil\"},\"sckPaymentLink\":\"sckPaymentLinkTest\",\"order_bump\":{\"parent_purchase_transaction\":\"HP02316330308193\",\"is_order_bump\":true},\"approved_date\":1511783346000,\"offer\":{\"code\":\"test\",\"coupon_code\":\"SHHUHA\"},\"is_funnel\":false,\"event_tickets\":{\"amount\":1757691206023},\"order_date\":1511783344000,\"price\":{\"currency_value\":\"BRL\",\"value\":1500},\"payment\":{\"installments_number\":12,\"type\":\"CREDIT_CARD\"},\"full_price\":{\"currency_value\":\"BRL\",\"value\":1500},\"business_model\":\"I\",\"transaction\":\"HP16015479281022\",\"status\":\"APPROVED\"},\"affiliates\":[{\"affiliate_code\":\"Q58388177J\",\"name\":\"Affiliate name\"}],\"producer\":{\"legal_nature\":\"Pessoa Física\",\"document\":\"12345678965\",\"name\":\"Producer Test Name\"},\"subscription\":{\"subscriber\":{\"code\":\"I9OT62C3\"},\"plan\":{\"name\":\"plano de teste\",\"id\":123},\"status\":\"ACTIVE\"},\"buyer\":{\"checkout_phone_code\":\"999999999\",\"address\":{\"zipcode\":\"38400123\",\"country\":\"Brasil\",\"number\":\"10\",\"address\":\"Avenida Francisco Galassi\",\"city\":\"Uberlândia\",\"state\":\"Minas Gerais\",\"neighborhood\":\"Tubalina\",\"complement\":\"Perto do shopping\",\"country_iso\":\"BR\"},\"document\":\"69526128664\",\"name\":\"Teste Comprador\",\"last_name\":\"Comprador\",\"checkout_phone\":\"99999999900\",\"first_name\":\"Teste\",\"email\":\"testeComprador271101postman15@example.com\",\"document_type\":\"CPF\"}},\"id\":\"173b4a45-8274-4102-99a6-6358e81c53fe\",\"creation_date\":1757691206054,\"event\":\"PURCHASE_APPROVED\",\"version\":\"2.0.0\"}', '{\"connection\":\"Close\",\"host\":\"erosvitta.com.br\",\"user-agent\":\"Jodd HTTP\",\"x-hotmart-hottok\":\"JXw991Om7EB7yY0Xf8ptOB4FJMlQaP534873\",\"x-https\":\"1\"}', '2025-09-12 12:33:27', 'falha', 'Payload inválido: falta email ou product_id', '2025-09-12 12:33:27'),
(11, 'hotmart', 'PURCHASE_APPROVED', 'JXw991Om7EB7yY0Xf8ptOB4FJMlQaP534873', '{\"data\":{\"product\":{\"support_email\":\"support@hotmart.com.br\",\"has_co_production\":false,\"name\":\"Produto test postback2\",\"warranty_date\":\"2017-12-27T00:00:00Z\",\"is_physical_product\":false,\"id\":0,\"ucode\":\"fb056612-bcc6-4217-9e6d-2a5d1110ac2f\",\"content\":{\"has_physical_products\":true,\"products\":[{\"name\":\"How to Make Clear Ice\",\"is_physical_product\":false,\"id\":4774438,\"ucode\":\"559fef42-3406-4d82-b775-d09bd33936b1\"},{\"name\":\"Organizador de Poeira\",\"is_physical_product\":true,\"id\":4999597,\"ucode\":\"099e7644-b7d1-43d6-82a9-ec6be0118a4b\"}]}},\"commissions\":[{\"currency_value\":\"BRL\",\"source\":\"MARKETPLACE\",\"value\":149.5},{\"currency_value\":\"BRL\",\"source\":\"PRODUCER\",\"value\":1350.5}],\"purchase\":{\"original_offer_price\":{\"currency_value\":\"BRL\",\"value\":1500},\"checkout_country\":{\"iso\":\"BR\",\"name\":\"Brasil\"},\"sckPaymentLink\":\"sckPaymentLinkTest\",\"order_bump\":{\"parent_purchase_transaction\":\"HP02316330308193\",\"is_order_bump\":true},\"approved_date\":1511783346000,\"offer\":{\"code\":\"test\",\"coupon_code\":\"SHHUHA\"},\"is_funnel\":false,\"event_tickets\":{\"amount\":1757691206023},\"order_date\":1511783344000,\"price\":{\"currency_value\":\"BRL\",\"value\":1500},\"payment\":{\"installments_number\":12,\"type\":\"CREDIT_CARD\"},\"full_price\":{\"currency_value\":\"BRL\",\"value\":1500},\"business_model\":\"I\",\"transaction\":\"HP16015479281022\",\"status\":\"APPROVED\"},\"affiliates\":[{\"affiliate_code\":\"Q58388177J\",\"name\":\"Affiliate name\"}],\"producer\":{\"legal_nature\":\"Pessoa Física\",\"document\":\"12345678965\",\"name\":\"Producer Test Name\"},\"subscription\":{\"subscriber\":{\"code\":\"I9OT62C3\"},\"plan\":{\"name\":\"plano de teste\",\"id\":123},\"status\":\"ACTIVE\"},\"buyer\":{\"checkout_phone_code\":\"999999999\",\"address\":{\"zipcode\":\"38400123\",\"country\":\"Brasil\",\"number\":\"10\",\"address\":\"Avenida Francisco Galassi\",\"city\":\"Uberlândia\",\"state\":\"Minas Gerais\",\"neighborhood\":\"Tubalina\",\"complement\":\"Perto do shopping\",\"country_iso\":\"BR\"},\"document\":\"69526128664\",\"name\":\"Teste Comprador\",\"last_name\":\"Comprador\",\"checkout_phone\":\"99999999900\",\"first_name\":\"Teste\",\"email\":\"testeComprador271101postman15@example.com\",\"document_type\":\"CPF\"}},\"hottok\":\"JXw991Om7EB7yY0Xf8ptOB4FJMlQaP534873\",\"id\":\"173b4a45-8274-4102-99a6-6358e81c53fe\",\"creation_date\":1757691206054,\"event\":\"PURCHASE_APPROVED\",\"version\":\"2.0.0\"}', '{\"connection\":\"Close\",\"host\":\"erosvitta.com.br\",\"user-agent\":\"Jodd HTTP\",\"x-hotmart-hottok\":\"JXw991Om7EB7yY0Xf8ptOB4FJMlQaP534873\",\"x-https\":\"1\"}', '2025-09-12 12:33:54', 'falha', 'Payload inválido: falta email ou product_id', '2025-09-12 12:33:54'),
(12, 'hotmart', 'hotmart', 'JXw991Om7EB7yY0Xf8ptOB4FJMlQaP534873', '[]', '{\"connection\":\"Keep-Alive\",\"host\":\"erosvitta.com.br\",\"user-agent\":\"Mozilla\\/5.0 (Windows NT; Windows NT 10.0; pt-BR) WindowsPowerShell\\/5.1.19041.6328\",\"x-hotmart-hottok\":\"JXw991Om7EB7yY0Xf8ptOB4FJMlQaP534873\",\"x-https\":\"1\"}', '2025-09-12 12:34:43', 'falha', 'Payload inválido: falta email ou product_id', '2025-09-12 12:34:43'),
(13, 'hotmart', 'PURCHASE_APPROVED', 'JXw991Om7EB7yY0Xf8ptOB4FJMlQaP534873', '{\"data\":{\"product\":{\"support_email\":\"support@hotmart.com.br\",\"has_co_production\":false,\"name\":\"Produto test postback2\",\"warranty_date\":\"2017-12-27T00:00:00Z\",\"is_physical_product\":false,\"id\":0,\"ucode\":\"fb056612-bcc6-4217-9e6d-2a5d1110ac2f\",\"content\":{\"has_physical_products\":true,\"products\":[{\"name\":\"How to Make Clear Ice\",\"is_physical_product\":false,\"id\":4774438,\"ucode\":\"559fef42-3406-4d82-b775-d09bd33936b1\"},{\"name\":\"Organizador de Poeira\",\"is_physical_product\":true,\"id\":4999597,\"ucode\":\"099e7644-b7d1-43d6-82a9-ec6be0118a4b\"}]}},\"commissions\":[{\"currency_value\":\"BRL\",\"source\":\"MARKETPLACE\",\"value\":149.5},{\"currency_value\":\"BRL\",\"source\":\"PRODUCER\",\"value\":1350.5}],\"purchase\":{\"original_offer_price\":{\"currency_value\":\"BRL\",\"value\":1500},\"checkout_country\":{\"iso\":\"BR\",\"name\":\"Brasil\"},\"sckPaymentLink\":\"sckPaymentLinkTest\",\"order_bump\":{\"parent_purchase_transaction\":\"HP02316330308193\",\"is_order_bump\":true},\"approved_date\":1511783346000,\"offer\":{\"code\":\"test\",\"coupon_code\":\"SHHUHA\"},\"is_funnel\":false,\"event_tickets\":{\"amount\":1757691206023},\"order_date\":1511783344000,\"price\":{\"currency_value\":\"BRL\",\"value\":1500},\"payment\":{\"installments_number\":12,\"type\":\"CREDIT_CARD\"},\"full_price\":{\"currency_value\":\"BRL\",\"value\":1500},\"business_model\":\"I\",\"transaction\":\"HP16015479281022\",\"status\":\"APPROVED\"},\"affiliates\":[{\"affiliate_code\":\"Q58388177J\",\"name\":\"Affiliate name\"}],\"producer\":{\"legal_nature\":\"Pessoa Física\",\"document\":\"12345678965\",\"name\":\"Producer Test Name\"},\"subscription\":{\"subscriber\":{\"code\":\"I9OT62C3\"},\"plan\":{\"name\":\"plano de teste\",\"id\":123},\"status\":\"ACTIVE\"},\"buyer\":{\"checkout_phone_code\":\"999999999\",\"address\":{\"zipcode\":\"38400123\",\"country\":\"Brasil\",\"number\":\"10\",\"address\":\"Avenida Francisco Galassi\",\"city\":\"Uberlândia\",\"state\":\"Minas Gerais\",\"neighborhood\":\"Tubalina\",\"complement\":\"Perto do shopping\",\"country_iso\":\"BR\"},\"document\":\"69526128664\",\"name\":\"Teste Comprador\",\"last_name\":\"Comprador\",\"checkout_phone\":\"99999999900\",\"first_name\":\"Teste\",\"email\":\"testeComprador271101postman15@example.com\",\"document_type\":\"CPF\"}},\"id\":\"173b4a45-8274-4102-99a6-6358e81c53fe\",\"creation_date\":1757691206054,\"event\":\"PURCHASE_APPROVED\",\"version\":\"2.0.0\"}', '{\"connection\":\"Close\",\"host\":\"erosvitta.com.br\",\"user-agent\":\"Jodd HTTP\",\"x-hotmart-hottok\":\"JXw991Om7EB7yY0Xf8ptOB4FJMlQaP534873\",\"x-https\":\"1\"}', '2025-09-12 12:48:29', 'falha', 'Payload inválido: falta email ou product_id', '2025-09-12 12:48:29'),
(14, 'hotmart', 'PURCHASE_APPROVED', 'JXw991Om7EB7yY0Xf8ptOB4FJMlQaP534873', '{\"data\":{\"product\":{\"support_email\":\"support@hotmart.com.br\",\"has_co_production\":false,\"name\":\"Produto test postback2\",\"warranty_date\":\"2017-12-27T00:00:00Z\",\"is_physical_product\":false,\"id\":0,\"ucode\":\"fb056612-bcc6-4217-9e6d-2a5d1110ac2f\",\"content\":{\"has_physical_products\":true,\"products\":[{\"name\":\"How to Make Clear Ice\",\"is_physical_product\":false,\"id\":4774438,\"ucode\":\"559fef42-3406-4d82-b775-d09bd33936b1\"},{\"name\":\"Organizador de Poeira\",\"is_physical_product\":true,\"id\":4999597,\"ucode\":\"099e7644-b7d1-43d6-82a9-ec6be0118a4b\"}]}},\"commissions\":[{\"currency_value\":\"BRL\",\"source\":\"MARKETPLACE\",\"value\":149.5},{\"currency_value\":\"BRL\",\"source\":\"PRODUCER\",\"value\":1350.5}],\"purchase\":{\"original_offer_price\":{\"currency_value\":\"BRL\",\"value\":1500},\"checkout_country\":{\"iso\":\"BR\",\"name\":\"Brasil\"},\"sckPaymentLink\":\"sckPaymentLinkTest\",\"order_bump\":{\"parent_purchase_transaction\":\"HP02316330308193\",\"is_order_bump\":true},\"approved_date\":1511783346000,\"offer\":{\"code\":\"test\",\"coupon_code\":\"SHHUHA\"},\"is_funnel\":false,\"event_tickets\":{\"amount\":1757691206023},\"order_date\":1511783344000,\"price\":{\"currency_value\":\"BRL\",\"value\":1500},\"payment\":{\"installments_number\":12,\"type\":\"CREDIT_CARD\"},\"full_price\":{\"currency_value\":\"BRL\",\"value\":1500},\"business_model\":\"I\",\"transaction\":\"HP16015479281022\",\"status\":\"APPROVED\"},\"affiliates\":[{\"affiliate_code\":\"Q58388177J\",\"name\":\"Affiliate name\"}],\"producer\":{\"legal_nature\":\"Pessoa Física\",\"document\":\"12345678965\",\"name\":\"Producer Test Name\"},\"subscription\":{\"subscriber\":{\"code\":\"I9OT62C3\"},\"plan\":{\"name\":\"plano de teste\",\"id\":123},\"status\":\"ACTIVE\"},\"buyer\":{\"checkout_phone_code\":\"999999999\",\"address\":{\"zipcode\":\"38400123\",\"country\":\"Brasil\",\"number\":\"10\",\"address\":\"Avenida Francisco Galassi\",\"city\":\"Uberlândia\",\"state\":\"Minas Gerais\",\"neighborhood\":\"Tubalina\",\"complement\":\"Perto do shopping\",\"country_iso\":\"BR\"},\"document\":\"69526128664\",\"name\":\"Teste Comprador\",\"last_name\":\"Comprador\",\"checkout_phone\":\"99999999900\",\"first_name\":\"Teste\",\"email\":\"testeComprador271101postman15@example.com\",\"document_type\":\"CPF\"}},\"hottok\":\"JXw991Om7EB7yY0Xf8ptOB4FJMlQaP534873\",\"id\":\"173b4a45-8274-4102-99a6-6358e81c53fe\",\"creation_date\":1757691206054,\"event\":\"PURCHASE_APPROVED\",\"version\":\"2.0.0\"}', '{\"connection\":\"Close\",\"host\":\"erosvitta.com.br\",\"user-agent\":\"Jodd HTTP\",\"x-hotmart-hottok\":\"JXw991Om7EB7yY0Xf8ptOB4FJMlQaP534873\",\"x-https\":\"1\"}', '2025-09-12 12:48:55', 'falha', 'Payload inválido: falta email ou product_id', '2025-09-12 12:48:55'),
(15, 'hotmart', 'hotmart', 'JXw991Om7EB7yY0Xf8ptOB4FJMlQaP534873', '[]', '{\"connection\":\"Keep-Alive\",\"host\":\"erosvitta.com.br\",\"user-agent\":\"Mozilla\\/5.0 (Windows NT; Windows NT 10.0; pt-BR) WindowsPowerShell\\/5.1.19041.6328\",\"x-hotmart-hottok\":\"JXw991Om7EB7yY0Xf8ptOB4FJMlQaP534873\",\"x-https\":\"1\"}', '2025-09-12 12:59:25', 'falha', 'Payload inválido: falta email ou product_id', '2025-09-12 12:59:25'),
(16, 'hotmart', 'PURCHASE_APPROVED', 'JXw991Om7EB7yY0Xf8ptOB4FJMlQaP534873', '{\"data\":{\"product\":{\"support_email\":\"support@hotmart.com.br\",\"has_co_production\":false,\"name\":\"Produto test postback2\",\"warranty_date\":\"2017-12-27T00:00:00Z\",\"is_physical_product\":false,\"id\":0,\"ucode\":\"fb056612-bcc6-4217-9e6d-2a5d1110ac2f\",\"content\":{\"has_physical_products\":true,\"products\":[{\"name\":\"How to Make Clear Ice\",\"is_physical_product\":false,\"id\":4774438,\"ucode\":\"559fef42-3406-4d82-b775-d09bd33936b1\"},{\"name\":\"Organizador de Poeira\",\"is_physical_product\":true,\"id\":4999597,\"ucode\":\"099e7644-b7d1-43d6-82a9-ec6be0118a4b\"}]}},\"commissions\":[{\"currency_value\":\"BRL\",\"source\":\"MARKETPLACE\",\"value\":149.5},{\"currency_value\":\"BRL\",\"source\":\"PRODUCER\",\"value\":1350.5}],\"purchase\":{\"original_offer_price\":{\"currency_value\":\"BRL\",\"value\":1500},\"checkout_country\":{\"iso\":\"BR\",\"name\":\"Brasil\"},\"sckPaymentLink\":\"sckPaymentLinkTest\",\"order_bump\":{\"parent_purchase_transaction\":\"HP02316330308193\",\"is_order_bump\":true},\"approved_date\":1511783346000,\"offer\":{\"code\":\"test\",\"coupon_code\":\"SHHUHA\"},\"is_funnel\":false,\"event_tickets\":{\"amount\":1757691206023},\"order_date\":1511783344000,\"price\":{\"currency_value\":\"BRL\",\"value\":1500},\"payment\":{\"installments_number\":12,\"type\":\"CREDIT_CARD\"},\"full_price\":{\"currency_value\":\"BRL\",\"value\":1500},\"business_model\":\"I\",\"transaction\":\"HP16015479281022\",\"status\":\"APPROVED\"},\"affiliates\":[{\"affiliate_code\":\"Q58388177J\",\"name\":\"Affiliate name\"}],\"producer\":{\"legal_nature\":\"Pessoa Física\",\"document\":\"12345678965\",\"name\":\"Producer Test Name\"},\"subscription\":{\"subscriber\":{\"code\":\"I9OT62C3\"},\"plan\":{\"name\":\"plano de teste\",\"id\":123},\"status\":\"ACTIVE\"},\"buyer\":{\"checkout_phone_code\":\"999999999\",\"address\":{\"zipcode\":\"38400123\",\"country\":\"Brasil\",\"number\":\"10\",\"address\":\"Avenida Francisco Galassi\",\"city\":\"Uberlândia\",\"state\":\"Minas Gerais\",\"neighborhood\":\"Tubalina\",\"complement\":\"Perto do shopping\",\"country_iso\":\"BR\"},\"document\":\"69526128664\",\"name\":\"Teste Comprador\",\"last_name\":\"Comprador\",\"checkout_phone\":\"99999999900\",\"first_name\":\"Teste\",\"email\":\"testeComprador271101postman15@example.com\",\"document_type\":\"CPF\"}},\"hottok\":\"JXw991Om7EB7yY0Xf8ptOB4FJMlQaP534873\",\"id\":\"173b4a45-8274-4102-99a6-6358e81c53fe\",\"creation_date\":1757691206054,\"event\":\"PURCHASE_APPROVED\",\"version\":\"2.0.0\"}', '{\"connection\":\"Close\",\"host\":\"erosvitta.com.br\",\"user-agent\":\"Jodd HTTP\",\"x-hotmart-hottok\":\"JXw991Om7EB7yY0Xf8ptOB4FJMlQaP534873\",\"x-https\":\"1\"}', '2025-09-12 13:16:56', 'falha', 'Produto não cadastrado para hotmart_product_id=0', '2025-09-12 13:16:53'),
(17, 'hotmart', 'PURCHASE_APPROVED', 'JXw991Om7EB7yY0Xf8ptOB4FJMlQaP534873', '{\"data\":{\"product\":{\"support_email\":\"support@hotmart.com.br\",\"has_co_production\":false,\"name\":\"Produto test postback2\",\"warranty_date\":\"2017-12-27T00:00:00Z\",\"is_physical_product\":false,\"id\":0,\"ucode\":\"fb056612-bcc6-4217-9e6d-2a5d1110ac2f\",\"content\":{\"has_physical_products\":true,\"products\":[{\"name\":\"How to Make Clear Ice\",\"is_physical_product\":false,\"id\":4774438,\"ucode\":\"559fef42-3406-4d82-b775-d09bd33936b1\"},{\"name\":\"Organizador de Poeira\",\"is_physical_product\":true,\"id\":4999597,\"ucode\":\"099e7644-b7d1-43d6-82a9-ec6be0118a4b\"}]}},\"commissions\":[{\"currency_value\":\"BRL\",\"source\":\"MARKETPLACE\",\"value\":149.5},{\"currency_value\":\"BRL\",\"source\":\"PRODUCER\",\"value\":1350.5}],\"purchase\":{\"original_offer_price\":{\"currency_value\":\"BRL\",\"value\":1500},\"checkout_country\":{\"iso\":\"BR\",\"name\":\"Brasil\"},\"sckPaymentLink\":\"sckPaymentLinkTest\",\"order_bump\":{\"parent_purchase_transaction\":\"HP02316330308193\",\"is_order_bump\":true},\"approved_date\":1511783346000,\"offer\":{\"code\":\"test\",\"coupon_code\":\"SHHUHA\"},\"is_funnel\":false,\"event_tickets\":{\"amount\":1757691206023},\"order_date\":1511783344000,\"price\":{\"currency_value\":\"BRL\",\"value\":1500},\"payment\":{\"installments_number\":12,\"type\":\"CREDIT_CARD\"},\"full_price\":{\"currency_value\":\"BRL\",\"value\":1500},\"business_model\":\"I\",\"transaction\":\"HP16015479281022\",\"status\":\"APPROVED\"},\"affiliates\":[{\"affiliate_code\":\"Q58388177J\",\"name\":\"Affiliate name\"}],\"producer\":{\"legal_nature\":\"Pessoa Física\",\"document\":\"12345678965\",\"name\":\"Producer Test Name\"},\"subscription\":{\"subscriber\":{\"code\":\"I9OT62C3\"},\"plan\":{\"name\":\"plano de teste\",\"id\":123},\"status\":\"ACTIVE\"},\"buyer\":{\"checkout_phone_code\":\"999999999\",\"address\":{\"zipcode\":\"38400123\",\"country\":\"Brasil\",\"number\":\"10\",\"address\":\"Avenida Francisco Galassi\",\"city\":\"Uberlândia\",\"state\":\"Minas Gerais\",\"neighborhood\":\"Tubalina\",\"complement\":\"Perto do shopping\",\"country_iso\":\"BR\"},\"document\":\"69526128664\",\"name\":\"Teste Comprador\",\"last_name\":\"Comprador\",\"checkout_phone\":\"99999999900\",\"first_name\":\"Teste\",\"email\":\"testeComprador271101postman15@example.com\",\"document_type\":\"CPF\"}},\"hottok\":\"JXw991Om7EB7yY0Xf8ptOB4FJMlQaP534873\",\"id\":\"173b4a45-8274-4102-99a6-6358e81c53fe\",\"creation_date\":1757691206054,\"event\":\"PURCHASE_APPROVED\",\"version\":\"2.0.0\"}', '{\"connection\":\"Close\",\"host\":\"erosvitta.com.br\",\"user-agent\":\"Jodd HTTP\",\"x-hotmart-hottok\":\"JXw991Om7EB7yY0Xf8ptOB4FJMlQaP534873\",\"x-https\":\"1\"}', '2025-09-12 13:17:13', 'falha', 'Produto não cadastrado para hotmart_product_id=0', '2025-09-12 13:17:11'),
(18, 'hotmart', 'PURCHASE_APPROVED', 'JXw991Om7EB7yY0Xf8ptOB4FJMlQaP534873', '{\"data\":{\"product\":{\"support_email\":\"support@hotmart.com.br\",\"has_co_production\":false,\"name\":\"Produto test postback2\",\"warranty_date\":\"2017-12-27T00:00:00Z\",\"is_physical_product\":false,\"id\":0,\"ucode\":\"fb056612-bcc6-4217-9e6d-2a5d1110ac2f\",\"content\":{\"has_physical_products\":true,\"products\":[{\"name\":\"How to Make Clear Ice\",\"is_physical_product\":false,\"id\":4774438,\"ucode\":\"559fef42-3406-4d82-b775-d09bd33936b1\"},{\"name\":\"Organizador de Poeira\",\"is_physical_product\":true,\"id\":4999597,\"ucode\":\"099e7644-b7d1-43d6-82a9-ec6be0118a4b\"}]}},\"commissions\":[{\"currency_value\":\"BRL\",\"source\":\"MARKETPLACE\",\"value\":149.5},{\"currency_value\":\"BRL\",\"source\":\"PRODUCER\",\"value\":1350.5}],\"purchase\":{\"original_offer_price\":{\"currency_value\":\"BRL\",\"value\":1500},\"checkout_country\":{\"iso\":\"BR\",\"name\":\"Brasil\"},\"sckPaymentLink\":\"sckPaymentLinkTest\",\"order_bump\":{\"parent_purchase_transaction\":\"HP02316330308193\",\"is_order_bump\":true},\"approved_date\":1511783346000,\"offer\":{\"code\":\"test\",\"coupon_code\":\"SHHUHA\"},\"is_funnel\":false,\"event_tickets\":{\"amount\":1757694018568},\"order_date\":1511783344000,\"price\":{\"currency_value\":\"BRL\",\"value\":1500},\"payment\":{\"installments_number\":12,\"type\":\"CREDIT_CARD\"},\"full_price\":{\"currency_value\":\"BRL\",\"value\":1500},\"business_model\":\"I\",\"transaction\":\"HP16015479281022\",\"status\":\"APPROVED\"},\"affiliates\":[{\"affiliate_code\":\"Q58388177J\",\"name\":\"Affiliate name\"}],\"producer\":{\"legal_nature\":\"Pessoa Física\",\"document\":\"12345678965\",\"name\":\"Producer Test Name\"},\"subscription\":{\"subscriber\":{\"code\":\"I9OT62C3\"},\"plan\":{\"name\":\"plano de teste\",\"id\":123},\"status\":\"ACTIVE\"},\"buyer\":{\"checkout_phone_code\":\"999999999\",\"address\":{\"zipcode\":\"38400123\",\"country\":\"Brasil\",\"number\":\"10\",\"address\":\"Avenida Francisco Galassi\",\"city\":\"Uberlândia\",\"state\":\"Minas Gerais\",\"neighborhood\":\"Tubalina\",\"complement\":\"Perto do shopping\",\"country_iso\":\"BR\"},\"document\":\"69526128664\",\"name\":\"Teste Comprador\",\"last_name\":\"Comprador\",\"checkout_phone\":\"99999999900\",\"first_name\":\"Teste\",\"email\":\"testeComprador271101postman15@example.com\",\"document_type\":\"CPF\"}},\"id\":\"278689e2-f660-4197-a34d-3508b07b423b\",\"creation_date\":1757694018639,\"event\":\"PURCHASE_APPROVED\",\"version\":\"2.0.0\"}', '{\"connection\":\"Close\",\"host\":\"erosvitta.com.br\",\"user-agent\":\"Jodd HTTP\",\"x-hotmart-hottok\":\"JXw991Om7EB7yY0Xf8ptOB4FJMlQaP534873\",\"x-https\":\"1\"}', '2025-09-12 13:20:21', 'falha', 'Produto não cadastrado para hotmart_product_id=0', '2025-09-12 13:20:19');

-- --------------------------------------------------------

--
-- Estrutura para view `v_acessos_ativos`
--
DROP TABLE IF EXISTS `v_acessos_ativos`;

CREATE ALGORITHM=UNDEFINED DEFINER=`paymen58`@`localhost` SQL SECURITY DEFINER VIEW `v_acessos_ativos`  AS SELECT `a`.`id` AS `acesso_id`, `a`.`usuario_id` AS `usuario_id`, `u`.`nome` AS `usuario_nome`, `u`.`email` AS `usuario_email`, `a`.`produto_id` AS `produto_id`, `p`.`titulo` AS `produto_titulo`, `p`.`tipo` AS `produto_tipo`, `a`.`origem` AS `origem`, `a`.`data_liberacao` AS `data_liberacao`, `a`.`criado_em` AS `acesso_criado_em` FROM ((`acessos` `a` join `usuarios` `u` on((`u`.`id` = `a`.`usuario_id`))) join `produtos` `p` on((`p`.`id` = `a`.`produto_id`))) WHERE (`a`.`status` = 'ativo') ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_dashboard_resumo`
--
DROP TABLE IF EXISTS `v_dashboard_resumo`;

CREATE ALGORITHM=UNDEFINED DEFINER=`paymen58`@`localhost` SQL SECURITY DEFINER VIEW `v_dashboard_resumo`  AS SELECT (select count(0) from `usuarios`) AS `usuarios_total`, (select count(0) from `usuarios` where (`usuarios`.`status` = 'ativo')) AS `usuarios_ativos`, (select count(0) from `produtos`) AS `produtos_total`, (select count(0) from `produtos` where (`produtos`.`ativo` = 1)) AS `produtos_ativos`, (select count(0) from `compras` where (`compras`.`status` = 'aprovada')) AS `vendas_confirmadas_total`, (select coalesce(sum(`compras`.`valor_pago`),0.00) from `compras` where (`compras`.`status` = 'aprovada')) AS `faturamento_total_confirmado`, (select count(0) from `compras` where ((`compras`.`status` = 'aprovada') and (cast(`compras`.`data_confirmacao` as date) = curdate()))) AS `vendas_hoje`, (select coalesce(sum(`compras`.`valor_pago`),0.00) from `compras` where ((`compras`.`status` = 'aprovada') and (cast(`compras`.`data_confirmacao` as date) = curdate()))) AS `faturamento_hoje` ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_downloads_pendentes`
--
DROP TABLE IF EXISTS `v_downloads_pendentes`;

CREATE ALGORITHM=UNDEFINED DEFINER=`paymen58`@`localhost` SQL SECURITY DEFINER VIEW `v_downloads_pendentes`  AS SELECT `c`.`id` AS `compra_id`, `c`.`usuario_id` AS `usuario_id`, `u`.`nome` AS `usuario_nome`, `c`.`produto_id` AS `produto_id`, `p`.`titulo` AS `produto_titulo`, `c`.`data_confirmacao` AS `data_confirmacao`, `c`.`data_liberacao` AS `data_liberacao`, timestampdiff(DAY,now(),`c`.`data_liberacao`) AS `dias_para_liberar` FROM ((`compras` `c` join `usuarios` `u` on((`u`.`id` = `c`.`usuario_id`))) join `produtos` `p` on((`p`.`id` = `c`.`produto_id`))) WHERE ((`c`.`status` = 'aprovada') AND (`c`.`data_liberacao` is not null) AND (now() < `c`.`data_liberacao`)) ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_faturamento_diario`
--
DROP TABLE IF EXISTS `v_faturamento_diario`;

CREATE ALGORITHM=UNDEFINED DEFINER=`paymen58`@`localhost` SQL SECURITY DEFINER VIEW `v_faturamento_diario`  AS SELECT cast(`c`.`data_confirmacao` as date) AS `dia`, count(0) AS `vendas`, coalesce(sum(`c`.`valor_pago`),0.00) AS `faturamento` FROM `compras` AS `c` WHERE (`c`.`status` = 'aprovada') GROUP BY cast(`c`.`data_confirmacao` as date) ORDER BY `dia` DESC ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_faturamento_mensal`
--
DROP TABLE IF EXISTS `v_faturamento_mensal`;

CREATE ALGORITHM=UNDEFINED DEFINER=`paymen58`@`localhost` SQL SECURITY DEFINER VIEW `v_faturamento_mensal`  AS SELECT date_format(`c`.`data_confirmacao`,'%Y-%m-01') AS `mes`, count(0) AS `vendas`, coalesce(sum(`c`.`valor_pago`),0.00) AS `faturamento` FROM `compras` AS `c` WHERE (`c`.`status` = 'aprovada') GROUP BY date_format(`c`.`data_confirmacao`,'%Y-%m-01') ORDER BY `mes` DESC ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_produtos_ativos`
--
DROP TABLE IF EXISTS `v_produtos_ativos`;

CREATE ALGORITHM=UNDEFINED DEFINER=`paymen58`@`localhost` SQL SECURITY DEFINER VIEW `v_produtos_ativos`  AS SELECT `p`.`id` AS `produto_id`, `p`.`titulo` AS `titulo`, `p`.`tipo` AS `tipo`, `p`.`ativo` AS `ativo`, coalesce(`vc`.`qtd_vendas_confirmadas`,0) AS `vendas_confirmadas`, coalesce(`aa`.`qtd_acessos_ativos`,0) AS `acessos_ativos` FROM ((`produtos` `p` left join (select `compras`.`produto_id` AS `produto_id`,count(0) AS `qtd_vendas_confirmadas` from `compras` where (`compras`.`status` = 'aprovada') group by `compras`.`produto_id`) `vc` on((`vc`.`produto_id` = `p`.`id`))) left join (select `acessos`.`produto_id` AS `produto_id`,count(0) AS `qtd_acessos_ativos` from `acessos` where (`acessos`.`status` = 'ativo') group by `acessos`.`produto_id`) `aa` on((`aa`.`produto_id` = `p`.`id`))) WHERE (`p`.`ativo` = 1) ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_top_produtos`
--
DROP TABLE IF EXISTS `v_top_produtos`;

CREATE ALGORITHM=UNDEFINED DEFINER=`paymen58`@`localhost` SQL SECURITY DEFINER VIEW `v_top_produtos`  AS SELECT `p`.`id` AS `produto_id`, `p`.`titulo` AS `titulo`, `p`.`tipo` AS `tipo`, count(`c`.`id`) AS `vendas_confirmadas`, coalesce(sum(`c`.`valor_pago`),0.00) AS `faturamento_confirmado` FROM (`produtos` `p` left join `compras` `c` on(((`c`.`produto_id` = `p`.`id`) and (`c`.`status` = 'aprovada')))) GROUP BY `p`.`id`, `p`.`titulo`, `p`.`tipo` ORDER BY `vendas_confirmadas` DESC, `faturamento_confirmado` DESC ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_usuarios_compras`
--
DROP TABLE IF EXISTS `v_usuarios_compras`;

CREATE ALGORITHM=UNDEFINED DEFINER=`paymen58`@`localhost` SQL SECURITY DEFINER VIEW `v_usuarios_compras`  AS SELECT `u`.`id` AS `usuario_id`, `u`.`nome` AS `nome`, `u`.`email` AS `email`, `u`.`status` AS `status`, `u`.`criado_em` AS `usuario_criado_em`, coalesce(`tc`.`total_compras`,0) AS `total_compras`, coalesce(`tc`.`total_confirmadas`,0) AS `total_confirmadas`, coalesce(`tc`.`total_gasto_confirmado`,0.00) AS `total_gasto_confirmado`, `tc`.`ultima_compra_confirmada_em` AS `ultima_compra_confirmada_em` FROM (`usuarios` `u` left join (select `compras`.`usuario_id` AS `usuario_id`,count(0) AS `total_compras`,sum((case when (`compras`.`status` = 'aprovada') then 1 else 0 end)) AS `total_confirmadas`,coalesce(sum((case when (`compras`.`status` = 'aprovada') then `compras`.`valor_pago` else 0 end)),0.00) AS `total_gasto_confirmado`,max((case when (`compras`.`status` = 'aprovada') then `compras`.`data_confirmacao` else NULL end)) AS `ultima_compra_confirmada_em` from `compras` group by `compras`.`usuario_id`) `tc` on((`tc`.`usuario_id` = `u`.`id`))) ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_vendas_confirmadas`
--
DROP TABLE IF EXISTS `v_vendas_confirmadas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`paymen58`@`localhost` SQL SECURITY DEFINER VIEW `v_vendas_confirmadas`  AS SELECT `c`.`id` AS `compra_id`, `c`.`hotmart_transaction_id` AS `hotmart_transaction_id`, `c`.`usuario_id` AS `usuario_id`, `u`.`nome` AS `usuario_nome`, `u`.`email` AS `usuario_email`, `c`.`produto_id` AS `produto_id`, `p`.`titulo` AS `produto_titulo`, `p`.`tipo` AS `produto_tipo`, `c`.`valor_pago` AS `valor_pago`, `c`.`moeda` AS `moeda`, `c`.`data_compra` AS `data_compra`, `c`.`data_confirmacao` AS `data_confirmacao`, `c`.`data_liberacao` AS `data_liberacao`, (case when ((`c`.`data_liberacao` is not null) and (now() >= `c`.`data_liberacao`)) then 1 else 0 end) AS `download_liberado` FROM ((`compras` `c` join `usuarios` `u` on((`u`.`id` = `c`.`usuario_id`))) join `produtos` `p` on((`p`.`id` = `c`.`produto_id`))) WHERE (`c`.`status` = 'aprovada') ;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `acessos`
--
ALTER TABLE `acessos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_acessos_usuario_produto` (`usuario_id`,`produto_id`),
  ADD KEY `idx_acessos_status` (`status`),
  ADD KEY `idx_acessos_liberacao` (`data_liberacao`),
  ADD KEY `fk_acessos_compra` (`compra_id`),
  ADD KEY `idx_acessos_usuario_status` (`usuario_id`,`status`),
  ADD KEY `idx_acessos_produto_status` (`produto_id`,`status`),
  ADD KEY `idx_acessos_liberacao_status` (`data_liberacao`,`status`),
  ADD KEY `idx_acessos_d7_email` (`data_liberacao`,`liberacao_email_status`);

--
-- Índices de tabela `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_admins_email` (`email`),
  ADD KEY `idx_admins_ativo` (`ativo`);

--
-- Índices de tabela `admin_password_resets`
--
ALTER TABLE `admin_password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_admin_password_resets_token` (`token`),
  ADD KEY `idx_admin_password_resets_admin` (`admin_id`),
  ADD KEY `idx_admin_password_resets_expira` (`expira_em`);

--
-- Índices de tabela `compras`
--
ALTER TABLE `compras`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_compras_hotmart_tx` (`hotmart_transaction_id`),
  ADD KEY `idx_compras_usuario` (`usuario_id`),
  ADD KEY `idx_compras_produto` (`produto_id`),
  ADD KEY `idx_compras_status` (`status`),
  ADD KEY `idx_compras_liberacao` (`data_liberacao`),
  ADD KEY `idx_compras_status_confirmacao` (`status`,`data_confirmacao`),
  ADD KEY `idx_compras_produto_status_confirmacao` (`produto_id`,`status`,`data_confirmacao`),
  ADD KEY `idx_compras_usuario_status` (`usuario_id`,`status`),
  ADD KEY `idx_compras_data_compra` (`data_compra`);

--
-- Índices de tabela `download_tokens`
--
ALTER TABLE `download_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_download_tokens_token` (`token`),
  ADD KEY `idx_download_tokens_usuario` (`usuario_id`),
  ADD KEY `idx_download_tokens_produto` (`produto_id`),
  ADD KEY `idx_download_tokens_expira` (`expira_em`),
  ADD KEY `idx_download_tokens_usuario_produto` (`usuario_id`,`produto_id`,`expira_em`);

--
-- Índices de tabela `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_password_resets_token` (`token`),
  ADD KEY `idx_password_resets_usuario` (`usuario_id`),
  ADD KEY `idx_password_resets_expira` (`expira_em`);

--
-- Índices de tabela `produtos`
--
ALTER TABLE `produtos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_produtos_slug` (`slug`),
  ADD UNIQUE KEY `uk_produtos_hotmart` (`hotmart_product_id`),
  ADD KEY `idx_produtos_tipo` (`tipo`),
  ADD KEY `idx_produtos_ativo` (`ativo`),
  ADD KEY `idx_produtos_ativo_tipo` (`ativo`,`tipo`);

--
-- Índices de tabela `snapshot_faturamento_diario`
--
ALTER TABLE `snapshot_faturamento_diario`
  ADD PRIMARY KEY (`dia`);

--
-- Índices de tabela `snapshot_resumo_diario`
--
ALTER TABLE `snapshot_resumo_diario`
  ADD PRIMARY KEY (`dia`);

--
-- Índices de tabela `snapshot_top_produtos_diario`
--
ALTER TABLE `snapshot_top_produtos_diario`
  ADD PRIMARY KEY (`dia`,`produto_id`),
  ADD KEY `idx_stpd_produto` (`produto_id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_usuarios_email` (`email`),
  ADD KEY `idx_usuarios_status` (`status`),
  ADD KEY `idx_usuarios_hotmart` (`hotmart_user_id`),
  ADD KEY `idx_usuarios_criado_em` (`criado_em`);

--
-- Índices de tabela `webhook_eventos`
--
ALTER TABLE `webhook_eventos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_webhook_eventos_origem` (`origem`),
  ADD KEY `idx_webhook_eventos_tipo` (`evento_tipo`),
  ADD KEY `idx_webhook_eventos_resultado` (`resultado_status`),
  ADD KEY `idx_webhook_eventos_criado_em` (`criado_em`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `acessos`
--
ALTER TABLE `acessos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `admins`
--
ALTER TABLE `admins`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `admin_password_resets`
--
ALTER TABLE `admin_password_resets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `compras`
--
ALTER TABLE `compras`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `download_tokens`
--
ALTER TABLE `download_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `produtos`
--
ALTER TABLE `produtos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `webhook_eventos`
--
ALTER TABLE `webhook_eventos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `acessos`
--
ALTER TABLE `acessos`
  ADD CONSTRAINT `fk_acessos_compra` FOREIGN KEY (`compra_id`) REFERENCES `compras` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_acessos_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_acessos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `admin_password_resets`
--
ALTER TABLE `admin_password_resets`
  ADD CONSTRAINT `fk_admin_password_resets_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `compras`
--
ALTER TABLE `compras`
  ADD CONSTRAINT `fk_compras_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_compras_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `download_tokens`
--
ALTER TABLE `download_tokens`
  ADD CONSTRAINT `fk_download_tokens_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_download_tokens_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `fk_password_resets_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `snapshot_top_produtos_diario`
--
ALTER TABLE `snapshot_top_produtos_diario`
  ADD CONSTRAINT `fk_stpd_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

DELIMITER $$
--
-- Eventos
--
CREATE DEFINER=`paymen58`@`localhost` EVENT `ev_daily_snapshots` ON SCHEDULE EVERY 1 DAY STARTS '2025-09-10 03:10:00' ON COMPLETION NOT PRESERVE ENABLE DO CALL sp_run_daily_snapshots(DATE(CONVERT_TZ(NOW(), '+00:00','-03:00')))$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
