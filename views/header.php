<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'ErosVitta - Área de Membros'; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/assets/css/style.css?v=<?php echo time(); ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300..700;1,300..700&family=Mulish:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet">
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <img src="<?php echo BASE_URL; ?>/public/assets/images/logo.png" alt="ErosVitta">
                <h1>ErosVitta</h1>
            </div>
            <div class="user-info">
                <span class="sans">Olá, <?php echo htmlspecialchars($user['nome']); ?>!</span>
                <a href="<?php echo BASE_URL; ?>/logout" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </div>
        </div>
    </header>
