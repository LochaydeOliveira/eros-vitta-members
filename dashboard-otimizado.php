<?php
// VERSÃO OTIMIZADA DO DASHBOARD
// Use esta versão para melhor performance

$pageTitle = 'Eros Vitta Members';
$currentPage = 'dashboard';

// Buscar materiais do usuário usando query otimizada
$db = Database::getInstance();
$userId = $_SESSION['user']['id'];

// Query otimizada usando a view criada
$materials = $db->fetchAll("
    SELECT DISTINCT 
        material_id as id,
        titulo,
        tipo,
        caminho,
        descricao,
        purchase_date,
        item_type,
        can_download
    FROM user_dashboard_materials 
    WHERE user_id = ? AND purchase_status = 'active'
    ORDER BY purchase_date DESC
", [$userId]);

// Se não encontrar materiais, usar sistema antigo
if (empty($materials)) {
    $materials = $db->fetchAll("
        SELECT m.*, um.liberado_em as purchase_date, 'legacy' as item_type,
               CASE 
                   WHEN m.tipo = 'ebook' AND DATEDIFF(NOW(), um.liberado_em) >= 7 
                   THEN 1 ELSE 0 
               END as can_download
        FROM user_materials um
        JOIN materials m ON um.material_id = m.id
        WHERE um.user_id = ?
        ORDER BY um.liberado_em DESC
    ", [$userId]);
}

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
                        <div class="material-icon">
                            <i class="fas fa-<?php echo getMaterialIcon($material['tipo']); ?>"></i>
                        </div>
                        <div class="material-info">
                            <h3><?= htmlspecialchars($material['titulo']) ?></h3>
                            <p class="material-type sans">
                                <?= ucfirst($material['tipo']) ?>
                            </p>
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

                            <!-- Download com controle otimizado -->
                            <?php if ($material['tipo'] === 'ebook'): ?>
                                <?php if ($material['can_download']): ?>
                                    <a href="<?= BASE_URL ?>/download/<?= $material['id'] ?>"
                                       class="btn btn-secondary">
                                        <i class="fas fa-download"></i>
                                        📥 Download PDF
                                    </a>
                                <?php else: ?>
                                    <?php
                                    $dataRef = new DateTime($material['purchase_date']);
                                    $agora = new DateTime();
                                    $diferenca = $agora->diff($dataRef);
                                    $diasRestantes = 7 - $diferenca->days;
                                    ?>
                                    <div class="download-locked">
                                        🔒 Download liberado em <?= $diasRestantes ?> dias
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
