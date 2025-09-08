<aside class="sidebar">
    <nav class="sidebar-nav">
        <ul>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/dashboard" class="nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <?php if (!empty($materials)): ?>
                <?php foreach ($materials as $material): ?>
                    <li class="nav-item">
                        <a href="<?php echo BASE_URL; ?>/<?php echo $material['tipo']; ?>/<?php echo $material['id']; ?>" 
                           class="nav-link <?php echo ($currentPage === $material['tipo'] && $currentMaterialId == $material['id']) ? 'active' : ''; ?>">
                            <i class="fas fa-<?php echo getMaterialIcon($material['tipo']); ?>"></i>
                            <span><?php echo htmlspecialchars($material['titulo']); ?></span>
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

<?php
// Função auxiliar para ícones dos materiais
function getMaterialIcon($tipo) {
    switch ($tipo) {
        case 'ebook':
            return 'book';
        case 'video':
            return 'play-circle';
        case 'audio':
            return 'headphones';
        default:
            return 'file';
    }
}
?>
