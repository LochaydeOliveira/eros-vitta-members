<?php
$pageTitle = 'Dashboard - ErosVitta';
$currentPage = 'dashboard';
include 'header.php';
include 'sidebar.php';
?>

<main class="main-content">
    <div class="dashboard-header">
        <h2>Bem-vindo à sua Área de Membros</h2>
        <p>Aqui você encontra todos os seus conteúdos exclusivos</p>
    </div>
    
    <div class="dashboard-content">
        <?php if (empty($materials)): ?>
            <div class="empty-state">
                <i class="fas fa-folder-open"></i>
                <h3>Nenhum material disponível</h3>
                <p>Você ainda não possui materiais liberados. Aguarde a confirmação da sua compra.</p>
            </div>
        <?php else: ?>
            <div class="materials-grid">
                <?php foreach ($materials as $material): ?>
                    <div class="material-card">
                        <div class="material-icon">
                            <i class="fas fa-<?php echo getMaterialIcon($material['tipo']); ?>"></i>
                        </div>
                        <div class="material-info">
                            <h3><?php echo htmlspecialchars($material['titulo']); ?></h3>
                            <p class="material-type">
                                <?php echo ucfirst($material['tipo']); ?>
                            </p>
                            <p class="material-date">
                                Liberado em: <?php echo date('d/m/Y H:i', strtotime($material['liberado_em'])); ?>
                            </p>
                        </div>
                        <div class="material-actions">
                            <a href="<?php echo BASE_URL; ?>/<?php echo $material['tipo']; ?>/<?php echo $material['id']; ?>" 
                               class="btn btn-primary">
                                <i class="fas fa-eye"></i>
                                Visualizar
                            </a>
                            
                            <?php
                            // Verifica se passou de 7 dias para liberar download
                            $liberadoEm = new DateTime($material['liberado_em']);
                            $agora = new DateTime();
                            $diferenca = $agora->diff($liberadoEm);
                            
                            if ($diferenca->days >= 7): ?>
                                <a href="<?php echo BASE_URL; ?>/download/<?php echo $material['id']; ?>" 
                                   class="btn btn-secondary">
                                    <i class="fas fa-download"></i>
                                    Download
                                </a>
                            <?php else: ?>
                                <span class="btn btn-disabled">
                                    <i class="fas fa-clock"></i>
                                    Download em <?php echo 7 - $diferenca->days; ?> dias
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'footer.php'; ?>
