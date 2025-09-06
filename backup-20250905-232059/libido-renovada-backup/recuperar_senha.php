<?php
require_once 'includes/init.php';

// Finalizar inicialização
finalizeInit();

// Verificar se já está logado
if (isLoggedIn()) {
    if (!headers_sent()) {
        header('Location: index.php');
        exit();
    }
    echo '<script>window.location.href = "index.php";</script>';
    exit();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Por favor, informe seu e-mail!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, informe um e-mail válido!';
    } else {
        try {
            // Verificar se o usuário existe
            $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE email = ? AND active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Gerar token único
                $token = bin2hex(random_bytes(32));
                $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Salvar token no banco
                $stmt = $pdo->prepare("INSERT INTO recuperacao_senha (user_id, token, expira, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$user['id'], $token, $expires_at]);
                
                // Gerar link de recuperação
                $reset_link = APP_URL . 'redefinir_senha.php?token=' . $token;
                
                // Enviar email
                if (function_exists('sendPasswordResetEmail')) {
                    $email_sent = sendPasswordResetEmail($user['email'], $user['name'], $reset_link);
                    
                    if ($email_sent) {
                        $message = 'Email de recuperação enviado! Verifique sua caixa de entrada.';
                    } else {
                        $error = 'Erro ao enviar email. Tente novamente ou entre em contato conosco.';
                    }
                } else {
                    $error = 'Sistema de email não disponível. Entre em contato conosco.';
                }
                
            } else {
                // Por segurança, não informar se o email existe ou não
                $message = 'Se o e-mail estiver cadastrado, você receberá as instruções de recuperação.';
            }
            
        } catch (Exception $e) {
            error_log("Erro na recuperação de senha: " . $e->getMessage());
            $error = 'Erro interno. Tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Recuperar Senha - ValidaPro</title>
    <link rel="icon" type="image/png" href="assets/img/favicon-oficial-validapro.png" />
    <link rel="apple-touch-icon" href="assets/img/favicon-oficial-validapro.png" />
    <link rel="preload" as="image" href="assets/svg/logo-valida-pro-em-svg.svg" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center">
    <div class="absolute inset-0 opacity-10 pointer-events-none z-0">
        <div class="absolute top-10 left-10 w-20 h-20 bg-white rounded-full"></div>
        <div class="absolute top-32 right-20 w-16 h-16 bg-white rounded-full"></div>
        <div class="absolute bottom-20 left-1/4 w-12 h-12 bg-white rounded-full"></div>
    </div>

    <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md relative z-10">
        <div class="text-center mb-8">
            <img src="assets/img/logo-validapro-checklist.svg" alt="ValidaPro Logo" class="h-16 mx-auto mb-4" />
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Recuperar Senha</h1>
            <p class="text-gray-600">Informe seu e-mail para receber as instruções</p>
        </div>

        <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <i class="fas fa-check-circle mr-2"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6" novalidate>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-envelope mr-2"></i>E-mail
                </label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    required
                    class="input-modern w-full"
                    placeholder="seu@email.com"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                />
            </div>

            <button
                type="submit"
                class="w-full btn-cta py-3 px-4 rounded-lg text-lg flex items-center justify-center gap-2"
            >
                <i class="fas fa-paper-plane"></i>Enviar Instruções
            </button>

            <div class="mt-4 text-center">
                <a href="login.php" class="text-sm text-orange-600 hover:text-orange-800 font-semibold transition">
                    <i class="fas fa-arrow-left mr-1"></i>Voltar ao Login
                </a>
            </div>
        </form>

        <div class="mt-8 text-center">
            <p class="text-sm text-gray-500">
                <i class="fas fa-info-circle mr-1"></i>
                As instruções serão enviadas para o e-mail cadastrado
            </p>
        </div>
    </div>
</body>
</html> 