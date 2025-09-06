<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'ErosVitta - Área de Membros'; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <img src="<?php echo BASE_URL; ?>/assets/images/logo.png" alt="ErosVitta">
                <h1>ErosVitta</h1>
            </div>
            <div class="user-info">
                <span>Olá, <?php echo htmlspecialchars($user['nome']); ?>!</span>
                <a href="<?php echo BASE_URL; ?>/logout" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </div>
        </div>
    </header>
