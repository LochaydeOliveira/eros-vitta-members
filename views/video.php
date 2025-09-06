<?php
$pageTitle = $material['titulo'] . ' - ErosVitta';
$currentPage = 'video';
$currentMaterialId = $material['id'];
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
            <i class="fas fa-play-circle"></i>
            Vídeo • Liberado em <?php echo date('d/m/Y H:i', strtotime($material['liberado_em'])); ?>
        </p>
    </div>
    
    <div class="material-content">
        <div class="video-viewer">
            <?php
            $filePath = STORAGE_PATH . '/' . $material['caminho'];
            
            if (file_exists($filePath)) {
                $extension = pathinfo($filePath, PATHINFO_EXTENSION);
                $mimeType = 'video/' . $extension;
                ?>
                <video controls width="100%" height="auto">
                    <source src="<?php echo BASE_URL; ?>/serve-file.php?id=<?php echo $material['id']; ?>" 
                            type="<?php echo $mimeType; ?>">
                    Seu navegador não suporta o elemento de vídeo.
                </video>
                <?php
            } else {
                echo '<div class="error-message">';
                echo '<i class="fas fa-exclamation-triangle"></i>';
                echo '<p>Arquivo de vídeo não encontrado.</p>';
                echo '</div>';
            }
            ?>
        </div>
        
        <div class="material-actions">
            <a href="<?php echo BASE_URL; ?>/download/<?php echo $material['id']; ?>" 
               class="btn btn-primary btn-large">
                <i class="fas fa-download"></i>
                Baixar Vídeo
            </a>
            
            <a href="<?php echo BASE_URL; ?>/dashboard" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i>
                Voltar ao Dashboard
            </a>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>
