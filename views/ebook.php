<?php
$pageTitle = $material['titulo'] . ' - ErosVitta';
$currentPage = 'ebook';
$currentMaterialId = $material['id'];
// Se o material for PDF, redireciona para o viewer PDF.js
$ext = strtolower(pathinfo($material['caminho'], PATHINFO_EXTENSION));
if ($ext === 'pdf') {
    header('Location: ' . BASE_URL . '/pdfjs/viewer.php?id=' . $currentMaterialId);
    exit;
}
include 'header.php';
include 'sidebar.php';
?>

<main class="main-content">
    <div class="material-header">
        <div class="breadcrumb">
            <a href="<?php echo BASE_URL; ?>/dashboard">Dashboard</a>
            <span>/</span>
            <span><?php echo htmlspecialchars($material['titulo']); ?></span>
        </div>
        
        <h1><?php echo htmlspecialchars($material['titulo']); ?></h1>
        <p class="material-meta">
            <i class="fas fa-book"></i>
            Ebook • Liberado em <?php echo date('d/m/Y H:i', strtotime($material['liberado_em'])); ?>
        </p>
    </div>
    
    <div class="material-content">
        <div class="ebook-viewer">
            <?php
            $filePath = STORAGE_PATH . '/' . $material['caminho'];
            
            if (file_exists($filePath)) {
                $extension = pathinfo($filePath, PATHINFO_EXTENSION);
                
                if ($extension === 'html') {
                    // Exibe conteúdo HTML diretamente
                    echo file_get_contents($filePath);
                } else {
                    // Para outros formatos, mostra mensagem
                    echo '<div class="file-preview">';
                    echo '<i class="fas fa-file-pdf"></i>';
                    echo '<p>Visualização não disponível para este formato.</p>';
                    echo '<p>Use o botão de download abaixo para acessar o arquivo.</p>';
                    echo '</div>';
                }
            } else {
                echo '<div class="error-message">';
                echo '<i class="fas fa-exclamation-triangle"></i>';
                echo '<p>Arquivo não encontrado.</p>';
                echo '</div>';
            }
            ?>
        </div>
        
        <div class="material-actions">
            <?php
            // Verifica se passou de 7 dias para liberar download
            $liberadoEm = new DateTime($material['liberado_em']);
            $agora = new DateTime();
            $diferenca = $agora->diff($liberadoEm);
            
            if ($diferenca->days >= 7): ?>
                <a href="<?php echo BASE_URL; ?>/download/<?php echo $material['id']; ?>" 
                   class="btn btn-primary btn-large">
                    <i class="fas fa-download"></i>
                    Baixar PDF
                </a>
            <?php else: ?>
                <div class="download-info">
                    <i class="fas fa-clock"></i>
                    <p>O download do PDF será liberado em <strong><?php echo 7 - $diferenca->days; ?> dias</strong>.</p>
                    <p>Por enquanto, você pode visualizar o conteúdo online acima.</p>
                </div>
            <?php endif; ?>
            
            <a href="<?php echo BASE_URL; ?>/dashboard" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i>
                Voltar ao Dashboard
            </a>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>
