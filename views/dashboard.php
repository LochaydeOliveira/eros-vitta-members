<?php
$pageTitle = 'Eros Vitta Members';
$currentPage = 'dashboard';

// Buscar materiais do usuário usando o novo sistema
$accessControl = new AccessControl();
$materials = $accessControl->getUserPurchasedMaterials($_SESSION['user']['id']);

include 'header.php';
include 'sidebar.php';
?>

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
                                <i class="fas fa-<?php echo getMaterialIcon($material['tipo']); ?>"></i>
                            </div>
                            <div class="material-badges">
                                <span class="material-type"><?= ucfirst($material['tipo']) ?></span>
                                <?php if (isset($material['item_type'])): ?>
                                    <span class="purchase-badge"><?= ucfirst($material['item_type']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="material-info">
                            <h3><?= htmlspecialchars($material['titulo']) ?></h3>
                            <p class="material-description"><?= htmlspecialchars($material['descricao']) ?></p>
                            <p class="material-date sans">
                                Comprado em: <?= date('d/m/Y H:i', strtotime($material['purchase_date'])) ?>
                            </p>
                        </div>
                        <div class="material-actions">
                            <!-- Visualização sempre liberada -->
                            <a href="<?= BASE_URL ?>/<?= $material['tipo'] ?>/<?= $material['id'] ?>" 
                               class="btn btn-primary">
                                <i class="fas fa-eye"></i>
                                📖 Visualizar
                            </a>
                            
                            <!-- Download com controle de tempo -->
                            <?php if ($material['tipo'] === 'ebook'): ?>
                                <?php if ($accessControl->canAccessMaterial($_SESSION['user']['id'], $material['id'], 'download')): ?>
                                    <a href="<?= BASE_URL ?>/download/<?= $material['id'] ?>" 
                                       class="btn btn-secondary">
                                        <i class="fas fa-download"></i>
                                        📥 Download PDF
                                    </a>
                                <?php else: ?>
                                    <div class="download-locked">
                                        🔒 Download liberado em <?= $accessControl->getDaysRemaining($material['purchase_date']) ?> dias
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

<?php include 'footer.php'; ?>
