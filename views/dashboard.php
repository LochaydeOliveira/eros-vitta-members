<?php
$pageTitle = 'Eros Vitta Members';
$currentPage = 'dashboard';

// Buscar materiais do usuário usando o sistema de compras
$db = Database::getInstance();
$userId = $_SESSION['user']['id'];

// Buscar materiais baseados nas compras do usuário
$materials = $db->fetchAll("
    SELECT DISTINCT m.*, up.purchase_date, up.item_type
    FROM user_purchases up
    LEFT JOIN product_material_mapping pmm ON up.hotmart_product_id = pmm.hotmart_product_id
    LEFT JOIN materials m ON pmm.material_id = m.id
    WHERE up.user_id = ? AND up.status = 'active' AND m.id IS NOT NULL
    ORDER BY up.purchase_date DESC
", [$userId]);

// Se não encontrar materiais no sistema novo, usar o sistema antigo
if (empty($materials)) {
    // Buscar materiais do sistema antigo (user_materials)
    $materials = $db->fetchAll("
        SELECT m.*, um.liberado_em as purchase_date, 'legacy' as item_type
        FROM user_materials um
        JOIN materials m ON um.material_id = m.id
        WHERE um.user_id = ?
        ORDER BY um.liberado_em DESC
    ", [$userId]);
}

include VIEWS_PATH . '/header.php';
include VIEWS_PATH . '/sidebar.php';
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
                        <div class="material-icon">
                            <i class="fas fa-<?php echo getMaterialIcon($material['tipo']); ?>"></i>
                        </div>
                        <div class="material-info">
                            <h3><?= htmlspecialchars($material['titulo']) ?></h3>
                            <p class="material-type sans">
                                <?= ucfirst($material['tipo']) ?>
                            </p>
                            <p class="material-date sans">
                                <?php if (isset($material['purchase_date'])): ?>
                                    Comprado em: <?= date('d/m/Y H:i', strtotime($material['purchase_date'])) ?>
                                <?php else: ?>
                                    Liberado em: <?= date('d/m/Y H:i', strtotime($material['liberado_em'])) ?>
                                <?php endif; ?>
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
                                        📥 Download PDF
                                    </a>
                                <?php else: ?>
                                    <div class="download-locked">
                                        🔒 Download liberado em <?= 7 - $diferenca->days ?> dias
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

<?php include VIEWS_PATH . '/footer.php'; ?>
