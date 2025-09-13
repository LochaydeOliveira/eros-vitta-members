<?php
// WebhookController.php atualizado com suporte a Order Bump e envio de email personalizado

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/email.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

// Função para enviar email de confirmação da compra
function enviarEmailCompra($email, $nome, $produtos, $isOrderBump = false) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;

        $mail->setFrom(SMTP_FROM, 'Eros Vitta Members');
        $mail->addAddress($email, $nome);

        $assunto = $isOrderBump ? 'Compra de Order Bump Confirmada' : 'Compra Confirmada';
        $mail->Subject = $assunto;

        $produtosHtml = '';
        foreach ($produtos as $p) {
            $produtosHtml .= "<li><b>{$p['nome']}</b></li>";
        }

        $mensagem = "<h3>Olá, {$nome}!</h3>";
        $mensagem .= "<p>Sua compra foi confirmada com sucesso.</p>";
        if ($isOrderBump) {
            $mensagem .= "<p>Você também adquiriu um produto complementar (Order Bump):</p>";
        }
        $mensagem .= "<ul>{$produtosHtml}</ul>";
        $mensagem .= "<p>Você pode acessar sua área de membros e visualizar seus produtos imediatamente.</p>";

        $mail->isHTML(true);
        $mail->Body = $mensagem;
        $mail->send();
    } catch (Exception $e) {
        error_log("Erro ao enviar email: {$mail->ErrorInfo}");
    }
}

// Captura e validação do webhook
$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Payload inválido']);
    exit;
}

$hotTok = $_SERVER['HTTP_X_HOTMART_HOTTOK'] ?? '';
if ($hotTok !== HOTMART_HOTTOK) {
    http_response_code(403);
    echo json_encode(['error' => 'Token inválido']);
    exit;
}

// Dados do comprador
$buyer = $data['data']['buyer'] ?? [];
$email = $buyer['email'] ?? '';
$nome = $buyer['name'] ?? '';

if (!$email) {
    http_response_code(400);
    echo json_encode(['error' => 'Email do comprador não encontrado']);
    exit;
}

// Conexão com banco
$conn = getConnection();

// Verifica ou cria usuário
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
$stmt->execute([$email]);
$userId = $stmt->fetchColumn();

if (!$userId) {
    $senha = bin2hex(random_bytes(4));
    $hash = password_hash($senha, PASSWORD_BCRYPT);

    $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha_hash) VALUES (?, ?, ?)");
    $stmt->execute([$nome, $email, $hash]);
    $userId = $conn->lastInsertId();
}

// Lista de produtos comprados
$produtos = $data['data']['product']['content']['products'] ?? [];
$isOrderBump = $data['data']['purchase']['order_bump']['is_order_bump'] ?? false;

// Salva compras no banco
foreach ($produtos as $produto) {
    $produtoId = $produto['id'] ?? null;
    $titulo = $produto['name'] ?? '';

    if (!$produtoId) continue;

    // Verifica se produto existe
    $stmt = $conn->prepare("SELECT id FROM produtos WHERE id = ?");
    $stmt->execute([$produtoId]);
    $exists = $stmt->fetchColumn();

    if (!$exists) {
        $stmt = $conn->prepare("INSERT INTO produtos (id, titulo, tipo) VALUES (?, ?, 'ebook')");
        $stmt->execute([$produtoId, $titulo]);
    }

    // Salva compra
    $stmt = $conn->prepare("INSERT INTO compras (usuario_id, produto_id, status, data_compra, data_liberacao) VALUES (?, ?, 'confirmado', NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY))");
    $stmt->execute([$userId, $produtoId]);
}

// Envia email de confirmação
$produtosEmail = [];
foreach ($produtos as $p) {
    $produtosEmail[] = ['nome' => $p['name'] ?? 'Produto'];
}

enviarEmailCompra($email, $nome, $produtosEmail, $isOrderBump);

echo json_encode(['status' => 'ok']);
?>
