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
$token_valid = false;
$user_data = null;

// Verificar token
$token = $_GET['token'] ?? '';
if (!empty($token)) {
    try {
        $stmt = $pdo->prepare("
            SELECT r.*, u.name, u.email 
            FROM recuperacao_senha r 
            JOIN users u ON r.user_id = u.id 
            WHERE r.token = ? AND r.expira > NOW() AND r.usado = 0
        ");
        $stmt->execute([$token]);
        $reset_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($reset_data) {
            $token_valid = true;
            $user_data = [
                'name' => $reset_data['name'],
                'email' => $reset_data['email'],
                'user_id' => $reset_data['user_id']
            ];
        } else {
            $error = 'Token inválido ou expirado. Solicite uma nova recuperação de senha.';
        }
    } catch (Exception $e) {
        error_log("Erro ao verificar token: " . $e->getMessage());
        $error = 'Erro interno. Tente novamente.';
    }
} else {
    $error = 'Token não fornecido. Acesse o link enviado por email.';
}

// Processar redefinição de senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valid) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($password)) {
        $error = 'Por favor, informe a nova senha!';
    } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
        $error = 'A senha deve ter pelo menos ' . PASSWORD_MIN_LENGTH . ' caracteres!';
    } elseif ($password !== $confirm_password) {
        $error = 'As senhas não coincidem!';
    } else {
        try {
            // Hash da nova senha
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Atualizar senha do usuário
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $user_data['user_id']]);
            
            // Marcar token como usado
            $stmt = $pdo->prepare("UPDATE recuperacao_senha SET usado = 1, usado_em = NOW() WHERE token = ?");
            $stmt->execute([$token]);
            
            $message = 'Senha alterada com sucesso! Você pode fazer login com sua nova senha.';
            
            // Redirecionar após 3 segundos
            header("refresh:3;url=login.php");
            
        } catch (Exception $e) {
            error_log("Erro ao redefinir senha: " . $e->getMessage());
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
    <title>Redefinir Senha - ValidaPro</title>
    <link rel="icon" type="image/png" href="assets/img/favicon-oficial-validapro.png" />
    <link rel="apple-touch-icon" href="assets/img/favicon-oficial-validapro.png" />
    <link rel="preload" as="image" href="assets/svg/logo-valida-pro-em-svg.svg" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
    <link href="assets/css/password-toggle.css" rel="stylesheet" />
    <script src="assets/js/password-toggle.js" defer></script>
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
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Redefinir Senha</h1>
            <?php if ($user_data): ?>
                <p class="text-gray-600">Olá, <?= htmlspecialchars($user_data['name']) ?>!</p>
            <?php endif; ?>
        </div>

        <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <i class="fas fa-check-circle mr-2"></i>
                <?= htmlspecialchars($message) ?>
                <p class="text-sm mt-2">Redirecionando para o login...</p>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($token_valid && !$message): ?>
            <form method="POST" class="space-y-6" novalidate>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-lock mr-2"></i>Nova Senha
                    </label>
                    <div class="relative">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                            minlength="<?= PASSWORD_MIN_LENGTH ?>"
                            class="input-modern w-full pr-10"
                            placeholder="••••••••"
                        />
                        <button
                            type="button"
                            id="togglePassword"
                            class="password-toggle-btn"
                            onclick="togglePasswordVisibility('password', 'togglePassword')"
                        >
                            <i class="fas fa-eye" id="passwordIcon"></i>
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Mínimo de <?= PASSWORD_MIN_LENGTH ?> caracteres</p>
                </div>

                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-lock mr-2"></i>Confirmar Nova Senha
                    </label>
                    <div class="relative">
                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            required
                            minlength="<?= PASSWORD_MIN_LENGTH ?>"
                            class="input-modern w-full pr-10"
                            placeholder="••••••••"
                        />
                        <button
                            type="button"
                            id="toggleConfirmPassword"
                            class="password-toggle-btn"
                            onclick="togglePasswordVisibility('confirm_password', 'toggleConfirmPassword')"
                        >
                            <i class="fas fa-eye" id="confirmPasswordIcon"></i>
                        </button>
                    </div>
                </div>

                <button
                    type="submit"
                    class="w-full btn-cta py-3 px-4 rounded-lg text-lg flex items-center justify-center gap-2"
                >
                    <i class="fas fa-key"></i>Alterar Senha
                </button>
            </form>
        <?php endif; ?>

        <div class="mt-6 text-center">
            <a href="login.php" class="text-sm text-orange-600 hover:text-orange-800 font-semibold transition">
                <i class="fas fa-arrow-left mr-1"></i>Voltar ao Login
            </a>
        </div>

        <?php if (!$token_valid && !$message): ?>
            <div class="mt-6 text-center">
                <a href="recuperar_senha.php" class="text-sm text-blue-600 hover:text-blue-800 font-semibold transition">
                    <i class="fas fa-redo mr-1"></i>Solicitar Nova Recuperação
                </a>
            </div>
        <?php endif; ?>

        <div class="mt-8 text-center">
            <p class="text-sm text-gray-500">
                <i class="fas fa-shield-alt mr-1"></i>
                Sua senha será alterada de forma segura
            </p>
        </div>
    </div>

    <script>
        // Validação em tempo real para confirmação de senha
        document.addEventListener('DOMContentLoaded', function() {
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            
            function validatePasswords() {
                if (password && confirmPassword && password.value && confirmPassword.value) {
                    if (password.value !== confirmPassword.value) {
                        confirmPassword.setCustomValidity('As senhas não coincidem');
                    } else {
                        confirmPassword.setCustomValidity('');
                    }
                }
            }
            
            if (password) password.addEventListener('input', validatePasswords);
            if (confirmPassword) confirmPassword.addEventListener('input', validatePasswords);
        });
    </script>
</body>
</html> 