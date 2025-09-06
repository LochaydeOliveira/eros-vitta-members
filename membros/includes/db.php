<?php
require_once __DIR__ . '/../config.php';

try {
    // Primeiro, tentar conectar sem especificar o banco
    $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Verificar se o banco existe
    $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "'");
    $databaseExists = $stmt->fetch();

    if (!$databaseExists) {
        // Criar o banco se não existir
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    // Agora conectar ao banco específico
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Criar tabelas se não existirem
    createTables($pdo);

} catch (PDOException $e) {
    // Log do erro sem exibir para o usuário
    error_log("Erro na conexão com o banco: " . $e->getMessage());
    $pdo = null;
}

function createTables($pdo) {
    if (!$pdo) return;

    try {
        // Tabela de usuários
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                name VARCHAR(255) NOT NULL,
                usuario VARCHAR(20) DEFAULT 'Cliente',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                active TINYINT(1) DEFAULT 1,
                last_login DATETIME DEFAULT NULL,
                reset_token VARCHAR(100) DEFAULT NULL,
                reset_token_expira DATETIME DEFAULT NULL,
                plano VARCHAR(50) DEFAULT 'Basic',
                whatsapp VARCHAR(20) DEFAULT NULL,
                observacoes TEXT DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Tabela de recuperação de senha
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS recuperacao_senha (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token VARCHAR(64) NOT NULL,
                expira DATETIME NOT NULL,
                usado TINYINT(1) DEFAULT 0,
                usado_em DATETIME DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
                INDEX idx_token (token),
                INDEX idx_expira (expira)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Adicionar coluna usado_em se não existir (para tabelas existentes)
        try {
            $pdo->exec("ALTER TABLE recuperacao_senha ADD COLUMN usado_em DATETIME DEFAULT NULL AFTER usado");
        } catch (PDOException $e) {
            // Coluna já existe, ignorar erro
        }

        // Tabela de resultados
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS results (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                promessa_principal TEXT,
                cliente_consciente TEXT,
                beneficios TEXT,
                mecanismo_unico TEXT,
                pontos INT DEFAULT 0,
                nota_final INT DEFAULT 0,
                mensagem VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Tabela de leads de prévias (landing pages)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS leads_previas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL,
                source VARCHAR(100) DEFAULT NULL,
                user_agent TEXT,
                ip VARCHAR(45) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Tabela de compras/entitlements básicos
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS purchases (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                product_slug VARCHAR(100) DEFAULT 'libido-renovado',
                status ENUM('approved','refunded','chargeback','pending','canceled') DEFAULT 'approved',
                approved_at DATETIME DEFAULT NULL,
                refunded_at DATETIME DEFAULT NULL,
                provider VARCHAR(50) DEFAULT 'hotmart',
                provider_payload JSON NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user (user_id),
                INDEX idx_status (status),
                INDEX idx_approved (approved_at),
                CONSTRAINT fk_purchases_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Inserir usuário padrão admin se não existir
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute(['admin@exemplo.com']);

        if ($stmt->fetchColumn() == 0) {
            $hashed_password = password_hash('123456', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (email, password, name, usuario, active) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute(['admin@exemplo.com', $hashed_password, 'Administrador', 'Administrador']);
        }

        // Inserir usuário Vera Soares se não existir
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute(['veramdssoares@gmail.com']);

        if ($stmt->fetchColumn() == 0) {
            $hashed_password = password_hash('Jw5$Gp8ews', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (email, password, name, usuario, active) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute(['veramdssoares@gmail.com', $hashed_password, 'Vera Soares', 'Cliente']);
        }

    } catch (PDOException $e) {
        error_log("Erro na criação das tabelas ou inserção de dados: " . $e->getMessage());
    }
}
