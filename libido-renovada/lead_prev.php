<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/email.php';
require_once __DIR__ . '/includes/db.php';

// Só define JSON se for uma chamada AJAX; se for via formulário padrão, redireciona
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
}

// Honeypot simples
if (!empty($_POST['website'] ?? '')) {
    http_response_code(200);
    echo json_encode(['ok' => true]);
    exit;
}

$email = trim($_POST['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    if (headers_sent()) { /* noop */ } else { http_response_code(400); }
    echo isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? json_encode(['ok' => false, 'message' => 'E-mail inválido']) : 'E-mail inválido';
    exit;
}

try {
    // Envia notificação para você (Zoho)
    $subjectOwner = 'Novo lead (prévia) - Libido Renovado';
    $bodyOwner = '<p>Você recebeu um novo pedido de prévia.</p>' .
                 '<p><strong>Email:</strong> ' . htmlspecialchars($email) . '</p>' .
                 '<p>Data: ' . date('d/m/Y H:i:s') . '</p>';
    $emailManager = new EmailManager();
    if (method_exists($emailManager, 'sendCustomEmail')) {
        $okOwner = $emailManager->sendCustomEmail(FROM_EMAIL, $subjectOwner, $bodyOwner);
    } else {
        $okOwner = $emailManager->sendGeneric(FROM_EMAIL, $subjectOwner, $bodyOwner);
    }

    // Salva lead no banco
    if (isset($pdo) && $pdo) {
        $stmt = $pdo->prepare("INSERT INTO leads_previas (email, source, user_agent, ip) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $email,
            'lp-libido-renovado',
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
    }

    // Envia auto-resposta para o lead
    $subjectLead = 'Sua prévia do Diário do Desejo';
    $htmlLead = "<div style='font-family: Mulish, Arial, sans-serif;'>
        <h2>Prévia do Diário do Desejo</h2>
        <p>Segue sua prévia gratuita. Em breve enviaremos novidades.</p>
        <p>Se preferir, você pode adquirir o Plano completo agora mesmo:</p>
        <p><a href='" . APP_URL . "lp-libido-renovado.html#checkout' style='background:#c67b54;color:#fff;padding:10px 16px;border-radius:6px;text-decoration:none;'>Comprar agora</a></p>
    </div>";
    if (method_exists($emailManager, 'sendCustomEmail')) {
        $okLead = $emailManager->sendCustomEmail($email, $subjectLead, $htmlLead);
    } else {
        $okLead = $emailManager->sendGeneric($email, $subjectLead, $htmlLead);
    }
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        echo json_encode(['ok' => ($okOwner && $okLead)]);
    } else {
        header('Location: lp-libido-renovado.html?prev=ok');
    }
} catch (Exception $e) {
    if (!headers_sent()) { http_response_code(500); }
    $msg = 'Erro ao enviar';
    echo isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? json_encode(['ok' => false, 'message' => $msg]) : $msg;
}


