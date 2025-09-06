-- Atualização idempotente do schema para o projeto Libido Renovado (MySQL 5.7+)
-- Este script pode ser executado várias vezes; cria/ajusta apenas o que faltar

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
/*!40101 SET NAMES utf8mb4 */;

DELIMITER $$
DROP PROCEDURE IF EXISTS ensure_schema $$
CREATE PROCEDURE ensure_schema()
BEGIN
  -- Tabelas base (CREATE IF NOT EXISTS)
  CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `usuario` VARCHAR(20) DEFAULT 'Cliente',
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `active` TINYINT(1) DEFAULT 1,
    `last_login` DATETIME DEFAULT NULL,
    `reset_token` VARCHAR(100) DEFAULT NULL,
    `reset_token_expira` DATETIME DEFAULT NULL,
    UNIQUE KEY `uniq_users_email` (`email`),
    KEY `idx_users_active` (`active`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

  CREATE TABLE IF NOT EXISTS `purchases` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `product_slug` VARCHAR(100) DEFAULT 'libido-renovado',
    `status` ENUM('approved','refunded','chargeback','pending','canceled') DEFAULT 'approved',
    `approved_at` DATETIME DEFAULT NULL,
    `refunded_at` DATETIME DEFAULT NULL,
    `provider` VARCHAR(50) DEFAULT 'hotmart',
    `provider_payload` JSON NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_purchases_user` (`user_id`),
    KEY `idx_purchases_status` (`status`),
    KEY `idx_purchases_approved` (`approved_at`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

  CREATE TABLE IF NOT EXISTS `recuperacao_senha` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `token` VARCHAR(64) NOT NULL,
    `expira` DATETIME NOT NULL,
    `usado` TINYINT(1) DEFAULT 0,
    `usado_em` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_token` (`token`),
    KEY `idx_expira` (`expira`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

  CREATE TABLE IF NOT EXISTS `results` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `promessa_principal` TEXT,
    `cliente_consciente` TEXT,
    `beneficios` TEXT,
    `mecanismo_unico` TEXT,
    `pontos` INT DEFAULT 0,
    `nota_final` INT DEFAULT 0,
    `mensagem` VARCHAR(255),
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

  CREATE TABLE IF NOT EXISTS `leads_previas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(255) NOT NULL,
    `source` VARCHAR(100) DEFAULT NULL,
    `user_agent` TEXT,
    `ip` VARCHAR(45) DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_leads_email` (`email`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

  -- Colunas adicionais em users (se faltarem)
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'plano'
  ) THEN
    ALTER TABLE `users` ADD COLUMN `plano` VARCHAR(20) DEFAULT 'Basic' AFTER `usuario`;
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'whatsapp'
  ) THEN
    ALTER TABLE `users` ADD COLUMN `whatsapp` VARCHAR(30) NULL AFTER `email`;
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'observacoes'
  ) THEN
    ALTER TABLE `users` ADD COLUMN `observacoes` TEXT NULL AFTER `whatsapp`;
  END IF;

  -- FKs (adicionar somente se não existirem)
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'fk_purchases_user'
  ) THEN
    ALTER TABLE `purchases`
      ADD CONSTRAINT `fk_purchases_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'fk_recuperacao_user'
  ) THEN
    ALTER TABLE `recuperacao_senha`
      ADD CONSTRAINT `fk_recuperacao_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'fk_results_user'
  ) THEN
    ALTER TABLE `results`
      ADD CONSTRAINT `fk_results_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
  END IF;

  -- Migração de dados: usuarios -> users (se tabela existir)
  IF EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'usuarios'
  ) THEN
    INSERT INTO `users` (`email`, `password`, `name`, `usuario`, `created_at`, `active`)
    SELECT u.`email`, u.`senha`, u.`nome`, 'Cliente', u.`criado_em`, COALESCE(u.`ativo`, 1)
    FROM `usuarios` u
    ON DUPLICATE KEY UPDATE
      `name` = VALUES(`name`),
      `password` = VALUES(`password`),
      `active` = VALUES(`active`);
  END IF;
END $$
DELIMITER ;

CALL ensure_schema();
DROP PROCEDURE IF EXISTS ensure_schema;


