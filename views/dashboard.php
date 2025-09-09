<?php
$pageTitle = 'Eros Vitta Members';
$currentPage = 'dashboard';

// Buscar materiais do usuário usando o sistema de compras
$db = Database::getInstance();
$userId = $_SESSION['user_id'] ?? ($_SESSION['user']['id'] ?? null);

// NOVO: obter todos os e-books e quais o usuário possui acesso
// 1) E-books do catálogo
$materials = $db->fetchAll("
    SELECT m.*, pmm.hotmart_product_id
    FROM materials m
    LEFT JOIN product_material_mapping pmm ON pmm.material_id = m.id
    WHERE m.tipo = 'ebook'
    ORDER BY m.titulo
", []);

// 2) IDs com acesso (sistema novo)
$purchasedNew = $db->fetchAll("
    SELECT DISTINCT m.id AS id
    FROM user_purchases up
    LEFT JOIN product_material_mapping pmm ON up.hotmart_product_id = pmm.hotmart_product_id
    LEFT JOIN materials m ON pmm.material_id = m.id
    WHERE up.user_id = ? AND up.status = 'active' AND m.id IS NOT NULL
", [$userId]);
// 3) IDs com acesso (legado)
$purchasedLegacy = $db->fetchAll("
    SELECT DISTINCT material_id AS id
    FROM user_materials
    WHERE user_id = ?
", [$userId]);

$userAccessIds = [];
foreach ([$purchasedNew, $purchasedLegacy] as $list) {
    foreach ($list as $row) { $userAccessIds[(int)$row['id']] = true; }
}
$purchasedCount = count($userAccessIds);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    
    <!-- Importar fontes do Google -->
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300..700;1,300..700&family=Mulish:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* Reset e configurações básicas */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --font-serif: 'Cormorant Garamond', serif;
            --font-sans: 'Mulish', sans-serif;
            --brand: #c67b54;
            --brand-dark: #8a573c;
            --bg: #f8f2ed;
            --text: #5a4134;
            --text-secondary: #7a5a49;
            --text-light: #9b7a67;
            --white: #ffffff;
            --border: #e9ecef;
        }

        body { font-family: var(--font-serif); line-height: 1.6; color: var(--text); background-color: var(--bg); }
        .sans { font-family: var(--font-sans); }

        /* Header */
        .header { background: var(--brand); color: white; padding: 1rem 0; position: fixed; top: 0; left: 0; right: 0; z-index: 1000; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header-content { max-width: 1200px; margin: 0 auto; padding: 0 2rem; display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 1.5rem; font-weight: 700; text-decoration: none; color: white; }
        .user-info { display: flex; align-items: center; gap: 1rem; }
        .logout-btn { background: rgba(255,255,255,0.2); color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; text-decoration: none; transition: background 0.3s; }
        .logout-btn:hover { background: rgba(255,255,255,0.3); }

        /* Sidebar (oculta) */
        .sidebar { display: none; }

        /* Main content */
        .main-content { margin-left: 0; margin-top: 80px; padding: 2rem; min-height: calc(100vh - 80px); }
        .container { max-width: 1000px; margin: 0 auto; }

        .dashboard-header { margin-bottom: 2rem; }
        .dashboard-header h2 { font-size: 2rem; margin-bottom: 0.5rem; color: var(--text); }
        .purchase-summary { background: var(--white); padding: 1rem; border-radius: 8px; border: 1px solid var(--border); margin-bottom: 1rem; }

        /* Grid */
        .materials-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.25rem; }
        .material-card { position: relative; background: var(--white); border-radius: 12px; padding: 1.25rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 1px solid var(--border); display: flex; flex-direction: column; gap: .75rem; }
        .material-cover { width: 100%; height: 160px; background: #fafafa; border: 1px solid var(--border); border-radius: 8px; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .material-cover img { width: 100%; height: 100%; object-fit: cover; }
        .material-card h3 { font-size: 1.1rem; color: var(--text); }
        .material-meta { color: var(--text-secondary); font-size: .9rem; }
        .material-actions { display: flex; gap: .5rem; flex-wrap: wrap; margin-top: .5rem; }

        .btn { display: inline-flex; align-items: center; gap: .5rem; padding: .6rem 1rem; border-radius: 6px; text-decoration: none; font-weight: 600; border: none; cursor: pointer; font-size: .9rem; }
        .btn-primary { background: var(--brand); color: white; }
        .btn-primary:hover { background: var(--brand-dark); }
        .btn-secondary { background: #6c757d; color: #fff; }
        .btn-disabled { background: #e9ecef; color: #6c757d; cursor: not-allowed; }

        .locked-overlay { position: absolute; inset: 0; background: rgba(255,255,255,0.6); border-radius: 12px; display: flex; align-items: center; justify-content: center; text-align: center; padding: 1rem; color: var(--text-secondary); font-weight: 600; }
        .locked-cta { margin-top: .5rem; display: inline-block; color: var(--brand); text-decoration: none; font-weight: 600; }
        .locked-cta:hover { color: var(--brand-dark); }

        @media (max-width: 768px) { .materials-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <a href="<?= BASE_URL ?>/dashboard" class="logo">Eros Vitta</a>
            <div class="user-info">
                <span class="sans">Olá, <?= htmlspecialchars($_SESSION['user']['nome']) ?>!</span>
                <a href="<?= BASE_URL ?>/logout" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Sair</a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
        <div class="dashboard-header">
            <h2>Eros Vitta Members</h2>
            <p class="sans">Bem-vindo, <?= htmlspecialchars($_SESSION['user']['nome']) ?>!</p>
            <div class="purchase-summary">
                <p class="sans">Você tem <strong><?= $purchasedCount ?></strong> e-book(s) liberado(s)</p>
            </div>
        </div>
        
        <div class="dashboard-content">
            <div class="materials-grid">
                <?php foreach ($materials as $material): ?>
                    <?php
                    $hasAccess = isset($userAccessIds[(int)$material['id']]);
                    $isPdf = isset($material['caminho']) && str_ends_with(strtolower($material['caminho']), '.pdf');

                    // Resolver PDF correspondente quando o caminho for HTML (usa correspondência por nome na pasta storage/pdfs)
                    $pdfFileRel = null;
                    if (!$isPdf && isset($material['caminho'])) {
                        $base = strtolower(pathinfo($material['caminho'], PATHINFO_FILENAME));
                        $pdfDir = STORAGE_PATH . '/pdfs';
                        if (is_dir($pdfDir)) {
                            foreach (glob($pdfDir . '/*.pdf') as $file) {
                                $name = strtolower(pathinfo($file, PATHINFO_FILENAME));
                                if (strpos($name, $base) !== false) {
                                    $pdfFileRel = 'pdfs/' . basename($file);
                                    break;
                                }
                            }
                        }
                    }

                    // Link de visualização: sempre usar viewer PDF.js quando houver PDF disponível
                    if ($hasAccess) {
                        if ($isPdf) {
                            $viewHref = BASE_URL . '/pdfjs/viewer.php?id=' . $material['id'];
                        } elseif ($pdfFileRel) {
                            $viewHref = BASE_URL . '/pdfjs/viewer.php?file=' . urlencode($pdfFileRel);
                        } else {
                            $viewHref = '#';
                        }
                    } else {
                        $viewHref = '#';
                    }

                    $checkoutHref = !empty($material['hotmart_product_id']) ? ('https://pay.hotmart.com/' . $material['hotmart_product_id'] . '?checkoutMode=10') : '#';

                    // Capa por código Hotmart
                    $cover = null;
                    $code = isset($material['hotmart_product_id']) ? $material['hotmart_product_id'] : null;
                    if ($code) {
                        $exts = ['jpg','jpeg','png','webp'];
                        foreach ($exts as $ext) {
                            foreach (glob(ROOT_PATH . '/assets/img/*' . $code . '.' . $ext) as $match) {
                                $cover = BASE_URL . '/assets/img/' . basename($match);
                                break 2;
                            }
                        }
                    }
                    if (!$cover) { $cover = BASE_URL . '/assets/img/thumbnail-vsl-libido-renovada.jpg'; }

                    // Data de referência para download (apenas se PDF nativo do material)
                    $refDate = null; $canDownload = false; $daysLeft = 7;
                    if ($hasAccess && ($isPdf)) {
                        $refRow = $db->fetch("SELECT up.purchase_date AS d FROM user_purchases up LEFT JOIN product_material_mapping pmm ON up.hotmart_product_id = pmm.hotmart_product_id WHERE up.user_id = ? AND pmm.material_id = ? ORDER BY up.purchase_date DESC LIMIT 1", [$userId, $material['id']]);
                        if ($refRow && !empty($refRow['d'])) { $refDate = $refRow['d']; }
                        if (!$refDate) {
                            $refRow = $db->fetch("SELECT liberado_em AS d FROM user_materials WHERE user_id = ? AND material_id = ? ORDER BY liberado_em DESC LIMIT 1", [$userId, $material['id']]);
                            if ($refRow && !empty($refRow['d'])) { $refDate = $refRow['d']; }
                        }
                        if ($refDate) {
                            $dataRef = new DateTime($refDate); $agora = new DateTime(); $diff = $agora->diff($dataRef);
                            $canDownload = ($diff->days >= 7);
                            $daysLeft = max(0, 7 - $diff->days);
                        }
                    }
                    ?>
                    <div class="material-card">
                        <div class="material-cover">
                            <img src="<?= $cover ?>" alt="Capa do e‑book">
                        </div>
                        <div>
                            <h3><?= htmlspecialchars($material['titulo']) ?></h3>
                            <p class="material-meta sans">E‑book • ID #<?= (int)$material['id'] ?></p>
                        </div>
                        <div class="material-actions">
                            <?php if ($hasAccess && $viewHref !== '#'): ?>
                                <a href="<?= $viewHref ?>" class="btn btn-primary"><i class="fas fa-eye"></i> Visualizar</a>
                            <?php elseif ($hasAccess): ?>
                                <button class="btn btn-disabled" disabled><i class="fas fa-eye-slash"></i> PDF indisponível</button>
                            <?php else: ?>
                                <button class="btn btn-disabled" disabled><i class="fas fa-lock"></i> Bloqueado</button>
                                <?php if ($checkoutHref !== '#'): ?>
                                    <a href="<?= $checkoutHref ?>" class="locked-cta sans">Liberar acesso</a>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if ($hasAccess && $isPdf): ?>
                                <?php if ($canDownload): ?>
                                    <a href="<?= BASE_URL ?>/download/<?= $material['id'] ?>" class="btn btn-secondary"><i class="fas fa-download"></i> Baixar PDF</a>
                                <?php else: ?>
                                    <button class="btn btn-disabled" disabled><i class="fas fa-clock"></i> Download em <?= $daysLeft ?> dias</button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <?php if (!$hasAccess): ?>
                            <div class="locked-overlay">
                                <div>
                                    <i class="fas fa-lock"></i> E‑book bloqueado
                                    <?php if ($checkoutHref !== '#'): ?>
                                        <div><a class="locked-cta" href="<?= $checkoutHref ?>">Clique para liberar</a></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        </div>
    </main>
</body>
</html>
