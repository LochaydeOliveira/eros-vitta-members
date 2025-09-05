<?php
require_once __DIR__ . '/conexao.php';

header('Content-Type: application/json; charset=UTF-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método não permitido']);
    exit;
}

$providedSecret = $_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? '';
if (!$providedSecret || !hash_equals((string)WEBHOOK_SHARED_SECRET, (string)$providedSecret)) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Não autorizado']);
    exit;
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$payload = [];
if (stripos($contentType, 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true) ?: [];
} else {
    $payload = $_POST;
}

$status = strtolower(trim($payload['status'] ?? $payload['purchase_status'] ?? $payload['event'] ?? ''));
$buyerEmail = trim($payload['email'] ?? $payload['buyer_email'] ?? ($payload['buyer']['email'] ?? ''));
$buyerName  = trim($payload['name']  ?? $payload['buyer_name'] ?? ($payload['buyer']['name'] ?? ''));

$approvedStatuses = ['approved', 'paid', 'confirmed', 'completed'];
if (!$buyerEmail || !in_array($status, $approvedStatuses, true)) {
    http_response_code(202);
    echo json_encode(['ok' => true, 'ignored' => true]);
    exit;
}

$stmt = $pdo->prepare('SELECT id, name, email, active FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$buyerEmail]);
$user = $stmt->fetch();

$loginUrl = rtrim(get_app_base_url(), '/') . '/login.php';

if ($user) {
    if ((int)$user['active'] !== 1) {
        $up = $pdo->prepare('UPDATE users SET active = 1 WHERE id = ?');
        $up->execute([(int)$user['id']]);
    }
    $html = '<p>Olá ' . htmlspecialchars($user['name'] ?: $buyerName) . ',</p>'
          . '<p>Seu acesso ao e-book <strong>Libido Renovado</strong> está ativo.</p>'
          . '<p>Use seu e-mail <b>' . htmlspecialchars($user['email']) . '</b> e a senha cadastrada para entrar.</p>'
          . '<p>Acesse: <a href="' . htmlspecialchars($loginUrl) . '">' . htmlspecialchars($loginUrl) . '</a></p>';
    send_app_email($user['email'], 'Acesso liberado - Libido Renovado', $html);
} else {
    $plainPass = generate_random_password(12);
    $hash = password_hash($plainPass, PASSWORD_DEFAULT);
    $nome = $buyerName ?: 'Cliente';
    $ativo = 1;
    $ins = $pdo->prepare('INSERT INTO users (name, email, password, active) VALUES (?, ?, ?, ?)');
    $ins->execute([$nome, $buyerEmail, $hash, $ativo]);

    $html = '<p>Olá ' . htmlspecialchars($nome) . ',</p>'
          . '<p>Bem-vindo! Aqui estão seus dados de acesso ao e-book <strong>Libido Renovado</strong>:</p>'
          . '<p><b>Login:</b> ' . htmlspecialchars($buyerEmail) . '<br>'
          . '<b>Senha:</b> ' . htmlspecialchars($plainPass) . '</p>'
          . '<p>Acesse: <a href="' . htmlspecialchars($loginUrl) . '">' . htmlspecialchars($loginUrl) . '</a></p>'
          . '<p>Recomenda-se alterar a senha após o primeiro acesso.</p>';
    send_app_email($buyerEmail, 'Seus dados de acesso - Libido Renovado', $html);
}

echo json_encode(['ok' => true]);
exit;


