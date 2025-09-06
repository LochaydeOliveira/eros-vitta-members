<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Área de Membros</title>
    <link href="../assets/css/tailwind.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-12 w-auto flex justify-center">
                <img class="h-12 w-auto" src="../assets/img/logo-libido-renovado.png" alt="Libido Renovado">
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Área de Membros
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Faça login para acessar seus conteúdos exclusivos
            </p>
        </div>
        
        <?php
        require_once 'config.php';
        require_once 'includes/db.php';
        require_once 'includes/auth.php';
        
        $error = '';
        $success = '';
        
        // Verificar se já está logado
        if (isLoggedIn()) {
            header('Location: index.php');
            exit();
        }
        
        // Processar login
        if ($_POST) {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (empty($email) || empty($password)) {
                $error = 'Por favor, preencha todos os campos.';
            } elseif (!validateEmail($email)) {
                $error = 'Por favor, insira um email válido.';
            } else {
                if (authenticateUser($email, $password)) {
                    app_log("Login bem-sucedido: $email");
                    header('Location: index.php');
                    exit();
                } else {
                    $error = 'Email ou senha incorretos.';
                    app_log("Tentativa de login falhou: $email");
                }
            }
        }
        
        // Verificar mensagens de erro/sucesso
        if (isset($_GET['error'])) {
            switch ($_GET['error']) {
                case 'timeout':
                    $error = 'Sua sessão expirou. Faça login novamente.';
                    break;
                case 'required':
                    $error = 'Você precisa fazer login para acessar esta área.';
                    break;
            }
        }
        
        if (isset($_GET['success'])) {
            switch ($_GET['success']) {
                case 'logout':
                    $success = 'Logout realizado com sucesso.';
                    break;
            }
        }
        ?>
        
        <form class="mt-8 space-y-6" method="POST">
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <div class="rounded-md shadow-sm -space-y-px">
                <div>
                    <label for="email" class="sr-only">Email</label>
                    <input id="email" name="email" type="email" autocomplete="email" required 
                           class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" 
                           placeholder="Seu email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div>
                    <label for="password" class="sr-only">Senha</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required 
                           class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" 
                           placeholder="Sua senha">
                </div>
            </div>

            <div>
                <button type="submit" 
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Entrar
                </button>
            </div>
            
            <div class="text-center">
                <p class="text-sm text-gray-600">
                    Não tem acesso? Entre em contato conosco.
                </p>
            </div>
        </form>
    </div>
</body>
</html>
