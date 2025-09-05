<?php
require 'conexao.php';
session_start();
$erro = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $senha = $_POST["senha"];

    // Consulta na tabela clientes
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE email = ? AND status = 'ativo'");
    $stmt->execute([$email]);
    $cliente = $stmt->fetch();

    if ($cliente && password_verify($senha, $cliente["senha"])) {
        $_SESSION["usuario"] = $email;
        $_SESSION["nome"] = $cliente["nome"];
        $_SESSION["cliente_id"] = $cliente["id"];
        $_SESSION["status"] = $cliente["status"];
        $_SESSION["classificacao"] = $cliente["classificacao"];

        // Redireciona para o painel
        header("Location: painel.php");
        exit;
    } else {
        $erro = "Email ou senha incorretos.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Login - Área de Clientes</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <link rel="icon" href="assets-agencia-led/img/icone-favorito-led.png" type="image/png">
    <link rel="apple-touch-icon" href="assets-agencia-led/img/icone-favorito-led.png">
    
    <style>
        body {
            background: #f0f2f5; /* Fundo cinza claro similar ao da Yampi */
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif; /* Usar a fonte Inter */
        }
        .form-container {
            background: #fff;
            border-radius: 8px; /* Bordas menos arredondadas */
            padding: 2rem; /* Ajusta o padding */
            box-shadow: 0 2px 10px rgba(0,0,0,0.08); /* Sombra mais suave */
            width: 100%;
            max-width: 380px; /* Largura máxima ajustada */
            text-align: center; /* Centraliza o conteúdo */
        }
        .logo-container {
            margin-bottom: 1.5rem;
        }
        .logo-container img {
            max-width: 85px; /* Ajuste o tamanho do logo se usar imagem */
            height: auto;
        }
        .form-title {
            font-size: 1.8rem; /* Tamanho do título */
            font-weight: 600; /* Peso da fonte */
            color: #333; /* Cor do texto */
            margin-bottom: 0.5rem; /* Espaço abaixo do título */
        }
        .form-subtitle {
            font-size: 1rem; /* Tamanho do subtítulo */
            color: #666; /* Cor do texto */
            margin-bottom: 2rem; /* Espaço abaixo do subtítulo */
        }
        .form-control {
            border-radius: 4px; /* Bordas arredondadas para inputs */
            padding: 0.75rem 1rem; /* Padding ajustado */
            border: 1px solid #ccc; /* Cor da borda */
        }
        .form-control:focus {
            border-color: #007bff; /* Cor da borda no foco */
            box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25); /* Sombra no foco */
        }
        .btn-custom {
            background-color: #000;
            border: none;
            color: white;
            padding: 9px;
            font-size: 15px;
            font-weight: 500;
            border-radius: 4px;
            transition: background-color 0.2s ease-in-out;
        }
        .btn-custom:hover {
            background-color: #333; /* Cor no hover */
            color: white;
        }
        .link-forgot-password {
            display: block;
            margin-top: 0.5rem;
            font-size: 0.86rem;
            color: #737373;
            text-decoration: underline;
        }
        .link-forgot-password:hover {
            text-decoration: underline;
            color: #000;
        }
        .create-account-text {
            margin-top: 1.5rem; /* Espaço acima do texto */
            font-size: 0.9rem; /* Tamanho da fonte */
            color: #666; /* Cor do texto */
        }
        .create-account-link {
            color: #007bff; /* Cor do link */
            text-decoration: none; /* Sem sublinhado */
            font-weight: 600; /* Peso da fonte */
        }
        .create-account-link:hover {
            text-decoration: underline; /* Sublinhado no hover */
        }
    </style>
</head>
<body>
<div class="form-container animate__animated animate__fadeIn">
    <div class="logo-container">
        <img width="100" height="60" src="assets-agencia-led/img/logo-led-preta.png" alt="logo oficial led - formulários">
    </div>
    <h2 class="form-title">Identifique-se</h2>
    <p class="form-subtitle">Digite seu e-mail e senha</p>
    <?php if ($erro): ?>
        <div class="alert alert-danger animate__animated animate__shakeX"><?php echo $erro; ?></div>
    <?php endif; ?>
    <form method="post">
        <div class="mb-3">
            <label for="email" class="form-label" style="text-align: left; display: block;">E-mail</label>
            <input type="email" id="email" name="email" class="form-control" placeholder="" required>
        </div>
        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center">
                 <label for="senha" class="form-label">Senha</label>
                 <a href="recuperar_senha.php" class="link-forgot-password">Esqueci minha senha</a>
            </div>
            <input type="password" id="senha" name="senha" class="form-control" placeholder="" required>
        </div>
        <div class="d-grid gap-2 mt-4">
            <button type="submit" class="btn btn-custom">Entrar</button>
        </div>
    </form>
</div>
</body>
</html>
