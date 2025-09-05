<?php
require_once __DIR__ . '/conexao.php';

if (is_logged_in()) {
    if (!headers_sent()) {
        header('Location: libido-renovado-content.html');
        exit();
    }
    echo '<script>window.location.href = "libido-renovado-content.html";</script>';
    exit();
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Por favor, preencha todos os campos!';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT id, name, email, password, active FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user && (int)$user['active'] === 1 && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                if (!headers_sent()) {
                    header('Location: libido-renovado-content.html');
                    exit();
                }
                echo '<script>window.location.href = "libido-renovado-content.html";</script>';
                exit();
            }
            $error = 'E-mail ou senha incorretos!';
        } catch (Throwable $e) {
            app_log('Erro no login: ' . $e->getMessage());
            $error = 'Falha ao autenticar. Tente novamente em instantes.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login - Libido Renovado</title>
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
            <img src="assets/img/logo-libido-renovado.png" alt="Libido Renovado Logo" class="h-16 mx-auto mb-4" />
            <p class="text-gray-700">Faça login para acessar seu e-book</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <?= $error ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'timeout'): ?>
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
                <i class="fas fa-clock mr-2"></i>
                Sua sessão expirou. Por favor, faça login novamente.
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6" novalidate>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-envelope mr-2"></i>Email
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

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-lock mr-2"></i>Senha
                </label>
                <div class="relative">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
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
            </div>

            <button
                type="submit"
                class="w-full btn-cta py-3 px-4 rounded-lg text-lg flex items-center justify-center gap-2"
            >
                <i class="fas fa-sign-in-alt"></i>Entrar
            </button>

            <div class="mt-4 text-center">
                <a href="recuperar_senha.php" class="text-sm text-orange-600 hover:text-orange-800 font-semibold transition"
                    >Esqueci minha senha?</a
                >
            </div>
        </form>


        <div class="mt-8 text-center">
            <p class="text-sm text-gray-500">
                <i class="fas fa-info-circle mr-1"></i>
                Acesso restrito - credenciais enviadas por email
            </p>
        </div>

                <!-- Adicionar link para o painel admin abaixo do formulário de login -->
        <!-- <div class="text-center mt-6">
            <a href="admin_clientes.php" class="text-sm text-gray-500 hover:underline">Sou Admin</a>
        </div> -->

        <?php if (defined('DEBUG_MODE') && DEBUG_MODE): ?>
            <div class="mt-6 p-4 bg-gray-100 rounded-lg">
                <h4 class="font-semibold text-gray-700 mb-2">Debug Info:</h4>
                <p class="text-xs text-gray-600">Session Status: <?= session_status() ?></p>
                <p class="text-xs text-gray-600">Session ID: <?= session_id() ?></p>
                <p class="text-xs text-gray-600">Headers Sent: <?= headers_sent() ? 'Sim' : 'Não' ?></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
