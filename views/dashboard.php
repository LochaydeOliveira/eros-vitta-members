<?php
$pageTitle = 'Eros Vitta Members';
$currentPage = 'dashboard';

// Buscar materiais do usuário usando o sistema de compras
$db = Database::getInstance();
$userId = $_SESSION['user_id'] ?? ($_SESSION['user']['id'] ?? null);

// Query otimizada para buscar materiais do usuário
$materials = $db->fetchAll("
    SELECT DISTINCT m.*, up.purchase_date, up.item_type, up.hotmart_product_id
    FROM user_purchases up
    LEFT JOIN product_material_mapping pmm ON up.hotmart_product_id = pmm.hotmart_product_id
    LEFT JOIN materials m ON IFNULL(up.material_id, pmm.material_id) = m.id
    WHERE up.user_id = ? AND up.status = 'active' AND m.id IS NOT NULL
    ORDER BY up.purchase_date DESC
", [$userId]);

// Se não encontrar materiais no sistema novo, usar o sistema antigo
if (empty($materials)) {
    $materials = $db->fetchAll("
        SELECT m.*, um.liberado_em as purchase_date, 'legacy' as item_type, 'LEGACY' as hotmart_product_id
        FROM user_materials um
        JOIN materials m ON um.material_id = m.id
        WHERE um.user_id = ?
        ORDER BY um.liberado_em DESC
    ", [$userId]);
}

// Função para ícones dos materiais
function getMaterialIcon($tipo) {
    switch ($tipo) {
        case 'ebook': return 'book';
        case 'video': return 'play-circle';
        case 'audio': return 'headphones';
        default: return 'file';
    }
}

// Função para emoji dos materiais
function getMaterialEmoji($tipo) {
    switch ($tipo) {
        case 'ebook': return '📖';
        case 'video': return '🎥';
        case 'audio': return '🎧';
        default: return '📄';
    }
}

// Função para cor do tipo de item
function getItemTypeColor($itemType) {
    switch ($itemType) {
        case 'main': return '#28a745'; // Verde
        case 'order_bump': return '#17a2b8'; // Azul
        case 'upsell': return '#ffc107'; // Amarelo
        case 'downsell': return '#fd7e14'; // Laranja
        case 'bonus': return '#6f42c1'; // Roxo
        case 'legacy': return '#6c757d'; // Cinza
        default: return '#6c757d';
    }
}

// Função para nome do tipo de item
function getItemTypeName($itemType) {
    switch ($itemType) {
        case 'main': return 'Produto Principal';
        case 'order_bump': return 'Order Bump';
        case 'upsell': return 'Upsell';
        case 'downsell': return 'Downsell';
        case 'bonus': return 'Bônus';
        case 'legacy': return 'Sistema Antigo';
        default: return ucfirst($itemType);
    }
}
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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

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

        body {
            font-family: var(--font-serif);
            line-height: 1.6;
            color: var(--text);
            background-color: var(--bg);
        }

        .sans {
            font-family: var(--font-sans);
        }

        /* Layout principal */
        .layout {
            display: flex;
            min-height: 100vh;
        }

        /* Header */
        .header {
            background: var(--brand);
            color: white;
            padding: 1rem 0;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            text-decoration: none;
            color: white;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.3s;
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background: var(--white);
            border-right: 1px solid var(--border);
            position: fixed;
            top: 80px;
            left: 0;
            height: calc(100vh - 80px);
            overflow-y: auto;
            z-index: 100;
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-item {
            list-style: none;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: var(--text);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }

        .nav-link:hover {
            background: var(--bg);
            color: var(--brand);
        }

        .nav-link.active {
            background: var(--bg);
            color: var(--brand);
            border-left-color: var(--brand);
        }

        .nav-link i {
            margin-right: 0.75rem;
            width: 20px;
        }

        .nav-link.disabled {
            color: var(--text-light);
            cursor: not-allowed;
        }

        /* Main content */
        .main-content {
            margin-left: 250px;
            margin-top: 80px;
            padding: 2rem;
            min-height: calc(100vh - 80px);
        }

        .dashboard-header {
            margin-bottom: 2rem;
        }

        .dashboard-header h2 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: var(--text);
        }

        .purchase-summary {
            background: var(--white);
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid var(--border);
            margin-bottom: 1rem;
        }

        /* Materials grid */
        .materials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .material-card {
            background: var(--white);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
            border: 1px solid var(--border);
        }

        .material-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.12);
        }

        .material-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .material-icon {
            font-size: 2rem;
            margin-right: 1rem;
        }

        .material-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 0.5rem;
        }

        .material-type {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }

        .item-type-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            color: white;
            margin-bottom: 0.5rem;
        }

        .material-date {
            font-size: 0.9rem;
            color: var(--text-light);
            margin-bottom: 1rem;
        }

        .material-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .btn i {
            margin-right: 0.5rem;
        }

        .btn-primary {
            background: var(--brand);
            color: white;
        }

        .btn-primary:hover {
            background: var(--brand-dark);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #545b62;
        }

        .download-locked {
            display: inline-flex;
            align-items: center;
            padding: 0.75rem 1rem;
            background: #f8f9fa;
            color: var(--text-light);
            border-radius: 6px;
            font-size: 0.9rem;
            border: 1px solid var(--border);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--white);
            border-radius: 12px;
            border: 1px solid var(--border);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--text-light);
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--text);
        }

        .empty-state p {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .materials-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <a href="<?= BASE_URL ?>/dashboard" class="logo">Eros Vitta</a>
            <div class="user-info">
                <span class="sans">Olá, <?= htmlspecialchars($_SESSION['user']['nome']) ?>!</span>
                <a href="<?= BASE_URL ?>/logout" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Sair
                </a>
            </div>
        </div>
    </header>

    <!-- Sidebar -->
    <aside class="sidebar">
        <nav class="sidebar-nav">
            <ul>
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>/dashboard" class="nav-link active">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                
                <?php if (!empty($materials)): ?>
                    <?php foreach ($materials as $material): ?>
                        <li class="nav-item">
                            <a href="<?= BASE_URL ?>/<?= $material['tipo'] ?>/<?= $material['id'] ?>" 
                               class="nav-link">
                                <i class="fas fa-<?= getMaterialIcon($material['tipo']) ?>"></i>
                                <span><?= htmlspecialchars($material['titulo']) ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="nav-item">
                        <span class="nav-link disabled">
                            <i class="fas fa-info-circle"></i>
                            <span>Nenhum material disponível</span>
                        </span>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="dashboard-header">
            <h2>Eros Vitta Members</h2>
            <p class="sans">Bem-vindo, <?= htmlspecialchars($_SESSION['user']['nome']) ?>!</p>
            <div class="purchase-summary">
                <p class="sans">Você tem <strong><?= count($materials) ?></strong> material(is) liberado(s)</p>
            </div>
        </div>
        
        <div class="dashboard-content">
            <?php if (empty($materials)): ?>
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <h3>Nenhum material disponível</h3>
                    <p class="sans">Você ainda não possui materiais liberados. Aguarde a confirmação da sua compra.</p>
                </div>
            <?php else: ?>
                <div class="materials-grid">
                    <?php foreach ($materials as $material): ?>
                        <div class="material-card">
                            <div class="material-header">
                                <div class="material-icon">
                                    <?= getMaterialEmoji($material['tipo']) ?>
                                </div>
                                <div>
                                    <h3 class="material-title"><?= htmlspecialchars($material['titulo']) ?></h3>
                                    <p class="material-type sans">
                                        <?= ucfirst($material['tipo']) ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="item-type-badge" style="background-color: <?= getItemTypeColor($material['item_type']) ?>">
                                <?= getItemTypeName($material['item_type']) ?>
                            </div>
                            
                            <p class="material-date sans">
                                <?php if (isset($material['purchase_date'])): ?>
                                    Comprado em: <?= date('d/m/Y H:i', strtotime($material['purchase_date'])) ?>
                                <?php else: ?>
                                    Liberado em: <?= date('d/m/Y H:i', strtotime($material['liberado_em'])) ?>
                                <?php endif; ?>
                            </p>
                            
                            <div class="material-actions">
                                <!-- Visualização sempre liberada -->
                                <a href="<?= BASE_URL ?>/<?= $material['tipo'] ?>/<?= $material['id'] ?>" 
                                   class="btn btn-primary">
                                    <i class="fas fa-eye"></i>
                                    Visualizar
                                </a>

                                <!-- Download com controle de tempo -->
                                <?php if ($material['tipo'] === 'ebook'): ?>
                                    <?php
                                    // Usar data de compra se disponível, senão usar data de liberação
                                    $dataReferencia = isset($material['purchase_date']) ? $material['purchase_date'] : $material['liberado_em'];
                                    $dataRef = new DateTime($dataReferencia);
                                    $agora = new DateTime();
                                    $diferenca = $agora->diff($dataRef);

                                    if ($diferenca->days >= 7): ?>
                                        <a href="<?= BASE_URL ?>/download/<?= $material['id'] ?>" 
                                           class="btn btn-secondary">
                                            <i class="fas fa-download"></i>
                                            Download PDF
                                        </a>
                                    <?php else: ?>
                                        <div class="download-locked">
                                            <i class="fas fa-lock"></i>
                                            Download liberado em <?= 7 - $diferenca->days ?> dias
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
