<?php
$pageTitle = $material['titulo'] . ' - ErosVitta';
$currentPage = 'audio';
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
            <i class="fas fa-headphones"></i>
            Áudio • Liberado em <?php echo date('d/m/Y H:i', strtotime($material['liberado_em'])); ?>
        </p>
    </div>
    
    <div class="material-content">
        <div class="audio-viewer">
            <?php
            $filePath = STORAGE_PATH . '/' . $material['caminho'];
            
            if (file_exists($filePath)) {
                $extension = pathinfo($filePath, PATHINFO_EXTENSION);
                $mimeType = 'audio/' . $extension;
                ?>
                <div class="audio-player">
                    <audio controls width="100%">
                        <source src="<?php echo BASE_URL; ?>/serve-file.php?id=<?php echo $material['id']; ?>" 
                                type="<?php echo $mimeType; ?>">
                        Seu navegador não suporta o elemento de áudio.
                    </audio>
                </div>
                <?php
            } else {
                echo '<div class="error-message">';
                echo '<i class="fas fa-exclamation-triangle"></i>';
                echo '<p>Arquivo de áudio não encontrado.</p>';
                echo '</div>';
            }
            ?>
        </div>
        
        <div class="material-actions">
            <a href="<?php echo BASE_URL; ?>/download/<?php echo $material['id']; ?>" 
               class="btn btn-primary btn-large">
                <i class="fas fa-download"></i>
                Baixar Áudio
            </a>
            
            <a href="<?php echo BASE_URL; ?>/dashboard" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i>
                Voltar ao Dashboard
            </a>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>
