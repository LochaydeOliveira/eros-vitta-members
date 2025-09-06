<?php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Verificar se está logado
requireLogin();

$user = getCurrentUser();

// Verificar se o usuário tem compra aprovada
$stmt = $pdo->prepare("
    SELECT approved_at, created_at 
    FROM purchases 
    WHERE user_id = ? AND status = 'approved' 
    ORDER BY approved_at DESC 
    LIMIT 1
");
$stmt->execute([$user['id']]);
$purchase = $stmt->fetch(PDO::FETCH_ASSOC);

$can_download = false;
if ($purchase && $purchase['approved_at']) {
    $approved_date = new DateTime($purchase['approved_at']);
    $now = new DateTime();
    $days_diff = $now->diff($approved_date)->days;
    
    if ($days_diff >= DOWNLOAD_DELAY_DAYS) {
        $can_download = true;
    }
}

// Listar e-books
$ebooks_dir = __DIR__ . '/e-books/';
$ebooks = [];
if (is_dir($ebooks_dir)) {
    $files = scandir($ebooks_dir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'pdf') {
            $ebooks[] = [
                'name' => pathinfo($file, PATHINFO_FILENAME),
                'file' => $file,
                'size' => filesize($ebooks_dir . $file)
            ];
        }
    }
}

// Listar áudios
$audios_dir = __DIR__ . '/audios/';
$audios = [];
if (is_dir($audios_dir)) {
    $files = scandir($audios_dir);
    foreach ($files as $file) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($ext, ['mp3', 'wav', 'm4a', 'ogg'])) {
            $audios[] = [
                'name' => pathinfo($file, PATHINFO_FILENAME),
                'file' => $file,
                'size' => filesize($audios_dir . $file)
            ];
        }
    }
}

// Criar diretório de áudios se não existir
if (!is_dir($audios_dir)) {
    mkdir($audios_dir, 0755, true);
    file_put_contents($audios_dir . '.gitkeep', '');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área de Membros - Libido Renovado</title>
    <link href="../assets/css/tailwind.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-6">
                <div class="flex items-center">
                    <img class="h-8 w-auto" src="../assets/img/logo-libido-renovado.png" alt="Libido Renovado">
                    <h1 class="ml-3 text-2xl font-bold text-gray-900">Área de Membros</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-700">Olá, <?= htmlspecialchars($user['name']) ?></span>
                    <a href="logout.php" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                        Sair
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
            <!-- Status de Download -->
            <?php if ($purchase): ?>
                <div class="mb-6">
                    <?php if ($can_download): ?>
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                            ✅ Downloads liberados! Você pode baixar todos os materiais.
                        </div>
                    <?php else: ?>
                        <?php 
                        $days_left = DOWNLOAD_DELAY_DAYS - $days_diff;
                        ?>
                        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
                            ⏳ Downloads serão liberados em <?= $days_left ?> dia(s). Por enquanto, você pode apenas visualizar os materiais.
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- E-books Section -->
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">📚 E-books</h2>
                <?php if (empty($ebooks)): ?>
                    <div class="bg-gray-100 border border-gray-300 text-gray-700 px-4 py-3 rounded">
                        Nenhum e-book disponível no momento.
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($ebooks as $ebook): ?>
                            <div class="bg-white rounded-lg shadow-md p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($ebook['name']) ?></h3>
                                    <span class="text-sm text-gray-500"><?= round($ebook['size'] / 1024 / 1024, 1) ?> MB</span>
                                </div>
                                <div class="flex space-x-2">
                                    <a href="view.php?type=ebook&file=<?= urlencode($ebook['file']) ?>" 
                                       class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium text-center">
                                        📖 Ler
                                    </a>
                                    <?php if ($can_download): ?>
                                        <a href="download.php?type=ebook&file=<?= urlencode($ebook['file']) ?>" 
                                           class="flex-1 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium text-center">
                                            ⬇️ Baixar
                                        </a>
                                    <?php else: ?>
                                        <button disabled 
                                                class="flex-1 bg-gray-400 text-white px-4 py-2 rounded-md text-sm font-medium text-center cursor-not-allowed">
                                            ⏳ Baixar
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Áudio-books Section -->
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">🎧 Áudio-books</h2>
                <?php if (empty($audios)): ?>
                    <div class="bg-gray-100 border border-gray-300 text-gray-700 px-4 py-3 rounded">
                        Nenhum áudio-book disponível no momento.
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($audios as $audio): ?>
                            <div class="bg-white rounded-lg shadow-md p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($audio['name']) ?></h3>
                                    <span class="text-sm text-gray-500"><?= round($audio['size'] / 1024 / 1024, 1) ?> MB</span>
                                </div>
                                
                                <!-- Player de áudio -->
                                <div class="mb-4">
                                    <audio controls class="w-full">
                                        <source src="view.php?type=audio&file=<?= urlencode($audio['file']) ?>" type="audio/mpeg">
                                        Seu navegador não suporta o elemento de áudio.
                                    </audio>
                                </div>
                                
                                <div class="flex space-x-2">
                                    <?php if ($can_download): ?>
                                        <a href="download.php?type=audio&file=<?= urlencode($audio['file']) ?>" 
                                           class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium text-center">
                                            ⬇️ Baixar
                                        </a>
                                    <?php else: ?>
                                        <button disabled 
                                                class="w-full bg-gray-400 text-white px-4 py-2 rounded-md text-sm font-medium text-center cursor-not-allowed">
                                            ⏳ Baixar
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Informações da Conta -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">ℹ️ Informações da Conta</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nome</label>
                        <p class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($user['name']) ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <p class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($user['email']) ?></p>
                    </div>
                    <?php if ($purchase): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Data da Compra</label>
                            <p class="mt-1 text-sm text-gray-900"><?= date('d/m/Y H:i', strtotime($purchase['approved_at'])) ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <p class="mt-1 text-sm text-gray-900">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Aprovado
                                </span>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
