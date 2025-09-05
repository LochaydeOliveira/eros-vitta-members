<?php
require_once 'includes/init.php';
requireLogin();
require_once 'includes/db.php';

// Permitir acesso apenas para administradores
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$stmt = $pdo->prepare("SELECT usuario FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user || strtolower($user['usuario']) !== 'administrador') {
    header('Location: index.php');
    exit;
}

// Buscar clientes
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

function statusBadge($active) {
    return $active ? '<span class="px-2 py-1 rounded bg-green-100 text-green-700 text-xs">Ativo</span>' : '<span class="px-2 py-1 rounded bg-red-100 text-red-700 text-xs">Inativo</span>';
}

function planoBadge($plano) {
    $cores = [
        'Basic' => 'bg-gray-200 text-gray-800',
        'Pro' => 'bg-blue-200 text-blue-800',
        'Anual' => 'bg-yellow-200 text-yellow-800'
    ];
    $cor = $cores[$plano] ?? 'bg-gray-100 text-gray-700';
    return "<span class='px-2 py-1 rounded $cor text-xs'>$plano</span>";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Painel de Clientes - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="assets/css/custom.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Clientes</h1>
            <button onclick="document.getElementById('modalNovoCliente').classList.remove('hidden')" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Novo Cliente</button>
        </div>
        <div class="bg-white rounded-xl shadow p-6 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nome</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">E-mail</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">WhatsApp</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Plano</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($clientes as $cliente): ?>
                    <tr>
                        <td class="px-4 py-2 text-gray-800 font-medium"><?php echo htmlspecialchars($cliente['name']); ?></td>
                        <td class="px-4 py-2 text-gray-700"><?php echo htmlspecialchars($cliente['email']); ?></td>
                        <td class="px-4 py-2 text-gray-700">
                            <?php if ($cliente['whatsapp']): ?>
                                <a href="https://wa.me/<?php echo preg_replace('/\D/', '', $cliente['whatsapp']); ?>" target="_blank" class="text-green-600 hover:underline"><?php echo htmlspecialchars($cliente['whatsapp']); ?></a>
                            <?php else: ?>
                                <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-2"><?php echo planoBadge($cliente['plano']); ?></td>
                        <td class="px-4 py-2"><?php echo statusBadge($cliente['active']); ?></td>
                        <td class="px-4 py-2 space-x-2">
                            <form method="post" action="admin_toggle_cliente.php" class="inline">
                                <input type="hidden" name="id" value="<?php echo $cliente['id']; ?>">
                                <button type="submit" class="px-2 py-1 rounded <?php echo $cliente['active'] ? 'bg-red-200 text-red-700 hover:bg-red-300' : 'bg-green-200 text-green-700 hover:bg-green-300'; ?> text-xs">
                                    <?php echo $cliente['active'] ? 'Desativar' : 'Ativar'; ?>
                                </button>
                            </form>
                            <?php if ($cliente['whatsapp']): ?>
                            <a href="https://wa.me/<?php echo preg_replace('/\D/', '', $cliente['whatsapp']); ?>" target="_blank" class="px-2 py-1 rounded bg-green-100 text-green-700 text-xs hover:bg-green-200">WhatsApp</a>
                            <?php endif; ?>
                            <form method="post" action="admin_reenviar_acesso.php" class="inline">
                                <input type="hidden" name="id" value="<?php echo $cliente['id']; ?>">
                                <button type="submit" class="px-2 py-1 rounded bg-blue-100 text-blue-700 text-xs hover:bg-blue-200">Reenviar Acesso</button>
                            </form>
                            <button onclick="editarCliente(<?php echo $cliente['id']; ?>)" class="px-2 py-1 rounded bg-yellow-100 text-yellow-700 text-xs hover:bg-yellow-200">Editar</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <!-- Modal Novo Cliente -->
        <div id="modalNovoCliente" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-xl shadow-xl p-8 w-full max-w-lg relative">
                <button onclick="document.getElementById('modalNovoCliente').classList.add('hidden')" class="absolute top-2 right-2 text-gray-400 hover:text-gray-700">&times;</button>
                <h2 class="text-2xl font-bold mb-4">Cadastrar Novo Cliente</h2>
                <form method="post" action="admin_novo_cliente.php" onsubmit="return confirmarEnvioAcesso()">
                    <div class="mb-4">
                        <label class="block text-gray-700 mb-1">Nome</label>
                        <input type="text" name="name" required class="w-full border rounded px-3 py-2">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 mb-1">E-mail</label>
                        <input type="email" name="email" required class="w-full border rounded px-3 py-2">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 mb-1">WhatsApp</label>
                        <input type="text" name="whatsapp" class="w-full border rounded px-3 py-2">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 mb-1">Plano</label>
                        <select name="plano" class="w-full border rounded px-3 py-2">
                            <option value="Basic">Basic</option>
                            <option value="Pro">Pro</option>
                            <option value="Anual">Anual</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 mb-1">Observações</label>
                        <textarea name="observacoes" class="w-full border rounded px-3 py-2"></textarea>
                    </div>
                    <div class="flex items-center mb-4">
                        <input type="checkbox" name="enviar_acesso" id="enviar_acesso" class="mr-2" checked>
                        <label for="enviar_acesso" class="text-gray-700">Enviar dados de acesso por e-mail</label>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Modal Editar Cliente (placeholder, pode ser implementado via JS/AJAX) -->
        <div id="modalEditarCliente" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-xl shadow-xl p-8 w-full max-w-lg relative">
                <button onclick="document.getElementById('modalEditarCliente').classList.add('hidden')" class="absolute top-2 right-2 text-gray-400 hover:text-gray-700">&times;</button>
                <h2 class="text-2xl font-bold mb-4">Editar Cliente</h2>
                <div id="editarClienteConteudo">Carregando...</div>
            </div>
        </div>
    </div>
    <script>
        function confirmarEnvioAcesso() {
            return confirm('Deseja enviar os dados de acesso para este cliente?');
        }
        function editarCliente(id) {
            document.getElementById('modalEditarCliente').classList.remove('hidden');
            document.getElementById('editarClienteConteudo').innerHTML = 'Carregando...';
            fetch('admin_editar_cliente.php?id=' + id)
                .then(resp => resp.text())
                .then(html => {
                    document.getElementById('editarClienteConteudo').innerHTML = html;
                });
        }

        // Confirmação para desativar/ativar
        document.querySelectorAll('form[action="admin_toggle_cliente.php"]').forEach(form => {
            form.addEventListener('submit', function(e) {
                const btn = this.querySelector('button[type="submit"]');
                const acao = btn && btn.textContent.includes('Desativar') ? 'desativar' : 'ativar';
                if (!confirm(`Tem certeza que deseja ${acao} este cliente?`)) {
                    e.preventDefault();
                }
            });
        });

        // Reenviar acesso via AJAX com confirmação e feedback
        document.querySelectorAll('form[action="admin_reenviar_acesso.php"]').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                if (!confirm('Deseja realmente reenviar os dados de acesso para este cliente?')) return;
                const btn = this.querySelector('button[type="submit"]');
                const original = btn.innerHTML;
                btn.innerHTML = '<svg class="animate-spin h-4 w-4 inline mr-1" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path></svg>Enviando...';
                btn.disabled = true;
                const formData = new FormData(this);
                fetch('admin_reenviar_acesso.php', { method: 'POST', body: formData })
                    .then(resp => resp.text())
                    .then(() => {
                        btn.innerHTML = '✔️ E-mail reenviado!';
                        btn.classList.add('bg-green-200','text-green-700');
                        setTimeout(() => {
                            btn.innerHTML = original;
                            btn.classList.remove('bg-green-200','text-green-700');
                            btn.disabled = false;
                        }, 2500);
                    })
                    .catch(() => {
                        btn.innerHTML = 'Erro ao reenviar';
                        btn.classList.add('bg-red-200','text-red-700');
                        setTimeout(() => {
                            btn.innerHTML = original;
                            btn.classList.remove('bg-red-200','text-red-700');
                            btn.disabled = false;
                        }, 2500);
                    });
            });
        });
    </script>
</body>
</html> 