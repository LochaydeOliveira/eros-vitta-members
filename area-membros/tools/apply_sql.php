<?php
require_once '../config.php';
require_once '../includes/db.php';

// Verificar token de acesso
$token = $_GET['token'] ?? '';
if ($token !== WEBHOOK_SHARED_SECRET) {
    http_response_code(401);
    die('Token de acesso inválido');
}

// Executar SQL
$sql_file = __DIR__ . '/../../sql/update_idempotent.sql';

if (!file_exists($sql_file)) {
    die('Arquivo SQL não encontrado: ' . $sql_file);
}

$sql_content = file_get_contents($sql_file);
$statements = explode(';', $sql_content);

$results = [];
$success_count = 0;
$error_count = 0;

foreach ($statements as $statement) {
    $statement = trim($statement);
    if (empty($statement)) continue;
    
    try {
        $pdo->exec($statement);
        $results[] = ['success' => true, 'sql' => substr($statement, 0, 100) . '...'];
        $success_count++;
    } catch (PDOException $e) {
        $results[] = ['success' => false, 'error' => $e->getMessage(), 'sql' => substr($statement, 0, 100) . '...'];
        $error_count++;
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplicar SQL</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .summary { background: #f0f0f0; padding: 10px; margin: 10px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Aplicação de SQL</h1>
    
    <div class="summary">
        <strong>Resumo:</strong><br>
        ✅ Sucessos: <?= $success_count ?><br>
        ❌ Erros: <?= $error_count ?><br>
        📄 Total: <?= count($results) ?>
    </div>
    
    <h2>Detalhes:</h2>
    <?php foreach ($results as $result): ?>
        <div class="<?= $result['success'] ? 'success' : 'error' ?>">
            <?= $result['success'] ? '✅' : '❌' ?> 
            <?= htmlspecialchars($result['sql']) ?>
            <?php if (!$result['success']): ?>
                <br><small>Erro: <?= htmlspecialchars($result['error']) ?></small>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    
    <p><a href="../index.php">← Voltar para área de membros</a></p>
</body>
</html>
