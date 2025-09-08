<?php
// Dashboard SUPER SIMPLES - sem dependências
session_start();
require_once 'app/config.php';
require_once 'app/db.php';

// Se não estiver logado, usar usuário padrão
if (!isset($_SESSION['user']['id'])) {
    $userId = 1;
    $userName = 'Lochayde Guerreiro (Teste)';
} else {
    $userId = $_SESSION['user']['id'];
    $userName = $_SESSION['user']['nome'];
}

$db = Database::getInstance();

// Query simples para buscar materiais
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
    $materials = $db->fetchAll("
        SELECT m.*, um.liberado_em as purchase_date, 'legacy' as item_type
        FROM user_materials um
        JOIN materials m ON um.material_id = m.id
        WHERE um.user_id = ?
        ORDER BY um.liberado_em DESC
    ", [$userId]);
}

// Função simples para ícones
function getIcon($tipo) {
    switch ($tipo) {
        case 'ebook': return '📖';
        case 'video': return '🎥';
        case 'audio': return '🎧';
        default: return '📄';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Super Simples - Eros Vitta</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            background: #f8f2ed;
            color: #5a4134;
        }
        .header {
            background: #c67b54;
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .material-card { 
            background: white;
            border: 1px solid #e9ecef; 
            padding: 20px; 
            margin: 15px 0; 
            border-radius: 8px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .material-card h3 { 
            color: #333; 
            margin: 0 0 10px 0; 
            font-size: 1.3em;
        }
        .material-card p { 
            margin: 5px 0; 
            color: #666; 
        }
        .btn { 
            display: inline-block; 
            padding: 10px 20px; 
            background: #c67b54; 
            color: white; 
            text-decoration: none; 
            border-radius: 5px; 
            margin: 5px 5px 5px 0; 
            font-weight: bold;
        }
        .btn:hover { 
            background: #8a573c; 
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #545b62;
        }
        .empty-state { 
            text-align: center; 
            padding: 40px; 
            color: #666; 
            background: white;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        .debug-info { 
            background: #f0f0f0; 
            padding: 15px; 
            margin: 15px 0; 
            border-radius: 5px; 
            font-size: 14px; 
            border-left: 4px solid #c67b54;
        }
        .icon {
            font-size: 2em;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎯 Eros Vitta Members</h1>
        <p>Bem-vindo, <?= htmlspecialchars($userName) ?>!</p>
    </div>
    
    <div class="debug-info">
        <strong>🔍 Debug Info:</strong><br>
        Usuário ID: <?= $userId ?><br>
        Total de materiais encontrados: <strong><?= count($materials) ?></strong><br>
        Sessão ativa: <?= isset($_SESSION['user']) ? 'Sim' : 'Não' ?><br>
        Nome do usuário: <?= $userName ?>
    </div>

    <h2>📚 Seus Materiais</h2>
    
    <?php if (empty($materials)): ?>
        <div class="empty-state">
            <h3>❌ Nenhum material disponível</h3>
            <p>Você ainda não possui materiais liberados.</p>
        </div>
    <?php else: ?>
        <div class="materials-list">
            <?php foreach ($materials as $material): ?>
                <div class="material-card">
                    <h3>
                        <span class="icon"><?= getIcon($material['tipo']) ?></span>
                        <?= htmlspecialchars($material['titulo']) ?>
                    </h3>
                    <p><strong>Tipo:</strong> <?= ucfirst($material['tipo']) ?></p>
                    <p><strong>Categoria:</strong> <?= $material['item_type'] ?></p>
                    <p><strong>Data de Compra:</strong> <?= date('d/m/Y H:i', strtotime($material['purchase_date'])) ?></p>
                    <p><strong>Arquivo:</strong> <?= htmlspecialchars($material['caminho']) ?></p>
                    
                    <div class="actions">
                        <a href="<?= BASE_URL ?>/<?= $material['tipo'] ?>/<?= $material['id'] ?>" class="btn">
                            📖 Visualizar
                        </a>
                        
                        <?php if ($material['tipo'] === 'ebook'): ?>
                            <?php
                            $dataRef = new DateTime($material['purchase_date']);
                            $agora = new DateTime();
                            $diferenca = $agora->diff($dataRef);
                            $diasRestantes = 7 - $diferenca->days;
                            ?>
                            
                            <?php if ($diferenca->days >= 7): ?>
                                <a href="<?= BASE_URL ?>/download/<?= $material['id'] ?>" class="btn btn-secondary">
                                    📥 Download PDF
                                </a>
                            <?php else: ?>
                                <span style="color: #999; font-style: italic;">
                                    🔒 Download liberado em <?= $diasRestantes ?> dias
                                </span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <hr style="margin: 30px 0;">
    <div style="text-align: center;">
        <p><a href="<?= BASE_URL ?>/dashboard" style="color: #c67b54;">← Voltar ao Dashboard Original</a></p>
        <p><a href="<?= BASE_URL ?>/teste-dashboard" style="color: #c67b54;">🧪 Teste com Rotas</a></p>
        <p><a href="<?= BASE_URL ?>/debug-dashboard" style="color: #c67b54;">🔍 Debug Completo</a></p>
    </div>
</body>
</html>
