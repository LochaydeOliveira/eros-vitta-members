<?php
if (session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Logging básico do módulo (em arquivo local)
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) { @mkdir($logDir, 0755, true); }
$logFile = $logDir . '/app-' . date('Y-m-d') . '.log';
@ini_set('log_errors', '1');
@ini_set('error_log', $logFile);
@ini_set('display_errors', '0');
@error_reporting(E_ALL);

function app_log($message): void {
    $user = $_SESSION['user_email'] ?? 'guest';
    $url = $_SERVER['REQUEST_URI'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $line = '[' . date('Y-m-d H:i:s') . "] [$user][$ip] $url - $message";
    error_log($line);
}

set_exception_handler(function(Throwable $e){
    app_log('EXCEPTION: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
});

set_error_handler(function($severity, $message, $file, $line){
    app_log("PHP ERROR [$severity]: $message in $file:$line");
    return false;
});

register_shutdown_function(function(){
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        app_log('FATAL: ' . $err['message'] . ' in ' . $err['file'] . ':' . $err['line']);
    }
});

// ===== Configuração de Banco (usar o DB unificado do projeto) =====
// Ajuste se necessário: primeiro tenta localhost, depois IP do provedor
define('DB_HOST_LOCAL_LIBIDO', 'localhost');
define('DB_HOST_IP_LIBIDO', '192.185.222.27');
define('DB_NAME_LIBIDO', 'paymen58_db_libido');
define('DB_USER_LIBIDO', 'paymen58');
define('DB_PASS_LIBIDO', 'u4q7+B6ly)obP_gxN9sNe');

// Webhook Secret (defina no provedor de checkout no header X-Webhook-Secret)
if (!defined('WEBHOOK_SHARED_SECRET')) {
    define('WEBHOOK_SHARED_SECRET', 'mude-este-segredo');
}

// E-mails admins (opcional)
if (!defined('ADMIN_ALLOWED_EMAILS')) {
    define('ADMIN_ALLOWED_EMAILS', json_encode(['lochaydeguerreiro@hotmail.com']));
}

// Conexão PDO (tenta localhost, depois IP)
$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

$dsnLocal = 'mysql:host=' . DB_HOST_LOCAL_LIBIDO . ';dbname=' . DB_NAME_LIBIDO . ';charset=utf8mb4';
$dsnIP    = 'mysql:host=' . DB_HOST_IP_LIBIDO    . ';dbname=' . DB_NAME_LIBIDO . ';charset=utf8mb4';

try {
    $pdo = new PDO($dsnLocal, DB_USER_LIBIDO, DB_PASS_LIBIDO, $pdoOptions);
    $pdo->query('SELECT 1');
} catch (PDOException $eLocal) {
    try {
        $pdo = new PDO($dsnIP, DB_USER_LIBIDO, DB_PASS_LIBIDO, $pdoOptions);
        $pdo->query('SELECT 1');
    } catch (PDOException $eIP) {
        error_log('Erro DB (localhost): ' . $eLocal->getMessage());
        error_log('Erro DB (IP): ' . $eIP->getMessage());
        http_response_code(500);
        die('Erro na conexão com o banco de dados.');
    }
}

// ===== Helpers de sessão/acesso =====
function is_logged_in(): bool { return !empty($_SESSION['user_id']); }
function require_login(): void { if (!is_logged_in()) { header('Location: login.php'); exit; } }
function is_admin(): bool {
    if (empty($_SESSION['user_email'])) return false;
    $allowed = json_decode(ADMIN_ALLOWED_EMAILS, true);
    return is_array($allowed) && in_array(strtolower($_SESSION['user_email']), array_map('strtolower', $allowed), true);
}
function require_admin(): void { if (!is_logged_in() || !is_admin()) { header('Location: login.php'); exit; } }

// ===== Utilitários =====
function get_app_base_url(): string {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    $base = $scheme . '://' . $host . ($scriptDir ? $scriptDir . '/' : '/');
    return $base;
}

function generate_random_password(int $length = 12): string {
    $length = max(8, min(64, $length));
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789@#$%';
    $bytes = random_bytes($length);
    $out = '';
    for ($i = 0; $i < $length; $i++) {
        $out .= $alphabet[ord($bytes[$i]) % strlen($alphabet)];
    }
    return $out;
}

// Envio de e-mail: tenta PHPMailer do projeto principal, senão fallback mail()
function send_app_email(string $toEmail, string $subject, string $htmlBody): bool {
    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        $autoload = __DIR__ . '/../validapro/vendor/autoload.php';
        if (is_file($autoload)) { @require_once $autoload; }
        $altPath = __DIR__ . '/../validapro/includes/email.php';
        if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer') && is_file($altPath)) { @require_once $altPath; }
    }

    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        try {
            $pm = new PHPMailer\\PHPMailer\\PHPMailer(true);
            $pm->CharSet = 'UTF-8';
            $pm->Encoding = 'base64';
            if (defined('SMTP_HOST') && defined('SMTP_USER') && defined('SMTP_PASS') && defined('SMTP_PORT')) {
                $pm->isSMTP();
                $pm->Host = SMTP_HOST;
                $pm->SMTPAuth = true;
                $pm->Username = SMTP_USER;
                $pm->Password = SMTP_PASS;
                $pm->SMTPSecure = PHPMailer\\PHPMailer\\PHPMailer::ENCRYPTION_STARTTLS;
                $pm->Port = SMTP_PORT;
            }
            $fromEmail = defined('SMTP_USER') ? SMTP_USER : 'no-reply@seu-dominio.com';
            $fromName  = 'Libido Renovado';
            $pm->setFrom($fromEmail, $fromName);
            $pm->addAddress($toEmail);
            $pm->isHTML(true);
            $pm->Subject = $subject;
            $pm->Body = $htmlBody;
            $pm->AltBody = strip_tags($htmlBody);
            return $pm->send();
        } catch (Throwable $e) {
            app_log('Erro PHPMailer: ' . $e->getMessage());
        }
    }

    $from = sprintf('%s <%s>', 'Libido Renovado', 'no-reply@seu-dominio.com');
    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=UTF-8';
    $headers[] = 'From: ' . $from;
    $headers[] = 'Reply-To: ' . $from;
    $headers[] = 'X-Mailer: PHP/' . phpversion();
    return @mail($toEmail, '=?UTF-8?B?' . base64_encode($subject) . '?=', $htmlBody, implode("\r\n", $headers));
}

?>


