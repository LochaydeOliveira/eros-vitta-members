<?php
require_once 'includes/init.php';
requireLogin();
require_once 'includes/db.php';

$id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$cliente) {
    echo '<div class="text-red-600">Cliente não encontrado.</div>';
    exit;
}
?>
<form method="post" action="admin_salvar_edicao_cliente.php">
    <input type="hidden" name="id" value="<?php echo $cliente['id']; ?>">
    <div class="mb-4">
        <label class="block text-gray-700 mb-1">Nome</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($cliente['name']); ?>" required class="w-full border rounded px-3 py-2">
    </div>
    <div class="mb-4">
        <label class="block text-gray-700 mb-1">E-mail</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($cliente['email']); ?>" required class="w-full border rounded px-3 py-2">
    </div>
    <div class="mb-4">
        <label class="block text-gray-700 mb-1">WhatsApp</label>
        <input type="text" name="whatsapp" value="<?php echo htmlspecialchars($cliente['whatsapp']); ?>" class="w-full border rounded px-3 py-2">
    </div>
    <div class="mb-4">
        <label class="block text-gray-700 mb-1">Plano</label>
        <select name="plano" class="w-full border rounded px-3 py-2">
            <option value="Basic" <?php if($cliente['plano']==='Basic') echo 'selected'; ?>>Basic</option>
            <option value="Pro" <?php if($cliente['plano']==='Pro') echo 'selected'; ?>>Pro</option>
            <option value="Anual" <?php if($cliente['plano']==='Anual') echo 'selected'; ?>>Anual</option>
        </select>
    </div>
    <div class="mb-4">
        <label class="block text-gray-700 mb-1">Observações</label>
        <textarea name="observacoes" class="w-full border rounded px-3 py-2"><?php echo htmlspecialchars($cliente['observacoes']); ?></textarea>
    </div>
    <div class="mb-4">
        <label class="block text-gray-700 mb-1">Status</label>
        <select name="active" class="w-full border rounded px-3 py-2">
            <option value="1" <?php if($cliente['active']) echo 'selected'; ?>>Ativo</option>
            <option value="0" <?php if(!$cliente['active']) echo 'selected'; ?>>Inativo</option>
        </select>
    </div>
    <div class="flex items-center mb-4">
        <input type="checkbox" name="reenviar_acesso" id="reenviar_acesso">
        <label for="reenviar_acesso" class="ml-2 text-gray-700">Reenviar dados de acesso por e-mail</label>
    </div>
    <div class="flex justify-end">
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Salvar Alterações</button>
    </div>
</form> 