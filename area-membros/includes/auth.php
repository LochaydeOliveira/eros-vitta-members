<?php
// Sistema de Autenticação e Sessão - ValidaPro

if (!isset($pdo)) {
    require_once __DIR__ . '/db.php';
}

// Inicializa sessão com configurações de segurança
function initSession() {
    // Verificar se já há output enviado
    if (headers_sent()) {
        error_log("AVISO: Headers já foram enviados antes de initSession()");
        return;
    }

    if (session_status() === PHP_SESSION_NONE) {
        @ini_set('session.cookie_httponly', 1);
        @ini_set('session.use_only_cookies', 1);
        @ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        @ini_set('session.cookie_samesite', 'Strict');
        @ini_set('session.gc_maxlifetime', SESSION_TIMEOUT ?? 3600);
        
        // Tentar iniciar sessão
        if (!session_start()) {
            error_log("ERRO: Não foi possível iniciar a sessão");
            return;
        }

        if (!isset($_SESSION['initialized'])) {
            session_regenerate_id(true);
            $_SESSION['initialized'] = true;
        }
        
        // Enviar headers de segurança após a sessão estar ativa
        if (function_exists('sendSecurityHeaders')) {
            sendSecurityHeaders();
        }
    }
}

// Autentica usuário
function authenticateUser($email, $password) {
    global $pdo;
    if (!$pdo) {
        error_log("Erro: Conexão com banco não disponível");
        return false;
    }

    initSession();

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $attempts_key = 'login_attempts_' . $ip;

    if (isset($_SESSION[$attempts_key]) && $_SESSION[$attempts_key]['count'] >= (MAX_LOGIN_ATTEMPTS ?? 5)) {
        $timeout = (LOGIN_TIMEOUT ?? 900);
        if (time() - $_SESSION[$attempts_key]['time'] < $timeout) {
            error_log("Tentativas de login excedidas para IP: $ip");
            return false;
        } else {
            unset($_SESSION[$attempts_key]);
        }
    }

    try {
        $stmt = $pdo->prepare("SELECT id, email, password, name, active, last_login FROM users WHERE email = ? AND active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Limpar tentativas de login
            unset($_SESSION[$attempts_key]);

            // Definir variáveis de sessão
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['login_time'] = time();
            $_SESSION['last_activity'] = time();
            $_SESSION['session_id'] = session_id();
            $_SESSION['ip_address'] = $ip;
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';

            // Atualizar último login no banco
            $update_stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $update_stmt->execute([$user['id']]);

            error_log("Login bem-sucedido: " . $user['email'] . " (IP: $ip)");

            return true;
        }

        // Falha de login: incrementa tentativas
        if (!isset($_SESSION[$attempts_key])) {
            $_SESSION[$attempts_key] = ['count' => 1, 'time' => time()];
        } else {
            $_SESSION[$attempts_key]['count']++;
            $_SESSION[$attempts_key]['time'] = time();
        }

        error_log("Tentativa de login falhou para: " . $email . " (IP: $ip)");
        return false;
    } catch (PDOException $e) {
        error_log("Erro na autenticação: " . $e->getMessage());
        return false;
    }
}

// Verifica se usuário está logado
function isLoggedIn() {
    initSession();

    if (!isset($_SESSION['user_id'], $_SESSION['login_time'], $_SESSION['last_activity'])) {
        return false;
    }

    $timeout = SESSION_TIMEOUT ?? 3600;

    if (time() - $_SESSION['last_activity'] > $timeout) {
        logout();
        return false;
    }

    $current_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if ($_SESSION['ip_address'] !== $current_ip) {
        error_log("Session hijacking suspeito - IP mudou de {$_SESSION['ip_address']} para $current_ip");
        logout();
        return false;
    }

    $current_ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if ($_SESSION['user_agent'] !== $current_ua) {
        error_log("Session hijacking suspeito - User Agent mudou");
        logout();
        return false;
    }

    $_SESSION['last_activity'] = time();
    return true;
}

// Requer login, redireciona se não autenticado
function requireLogin() {
    if (!isLoggedIn()) {
        if (ob_get_length()) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            header('Location: login.php');
            exit();
        }
        echo '<script>window.location.href = "login.php";</script>';
        exit();
    }
}

// Logout seguro
function logout() {
    initSession();
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

// Pega dados do usuário atual
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return [
        'id' => $_SESSION['user_id'],
        'email' => $_SESSION['user_email'],
        'name' => $_SESSION['user_name']
    ];
}

// Valida email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Verificar timeout da sessão
function checkSessionTimeout() {
    initSession();
    
    if (!isset($_SESSION['last_activity'])) {
        return;
    }
    
    $timeout = SESSION_TIMEOUT ?? 3600;
    
    if (time() - $_SESSION['last_activity'] > $timeout) {
        error_log("Sessão expirada para usuário: " . ($_SESSION['user_email'] ?? 'desconhecido'));
        logout();
        if (!headers_sent()) {
            header('Location: login.php?error=timeout');
            exit();
        }
        echo '<script>window.location.href = "login.php?error=timeout";</script>';
        exit();
    }
}

// Renovar sessão
function renewSession() {
    initSession();
    
    if (isset($_SESSION['last_activity'])) {
        $regeneration_time = SESSION_REGENERATION_TIME ?? 300;
        
        if (time() - $_SESSION['last_activity'] > $regeneration_time) {
            // Regenerar ID da sessão para prevenir session fixation
            session_regenerate_id(true);
            $_SESSION['session_id'] = session_id();
            error_log("Sessão regenerada para usuário: " . ($_SESSION['user_email'] ?? 'desconhecido'));
        }
        
        $_SESSION['last_activity'] = time();
    }
}

// Função para limpar sessões expiradas (pode ser chamada por cron)
function cleanupExpiredSessions() {
    $timeout = SESSION_TIMEOUT ?? 3600;
    $expired_time = time() - $timeout;
    
    // Limpar dados de tentativas de login expiradas
    foreach ($_SESSION as $key => $value) {
        if (strpos($key, 'login_attempts_') === 0 && isset($value['time']) && $value['time'] < $expired_time) {
            unset($_SESSION[$key]);
        }
    }
}
