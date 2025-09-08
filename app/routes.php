<?php
require_once 'auth.php';
require_once 'mailer.php';
require_once 'db.php';
require_once 'accessControl.php';

class Router {
    private $routes = [];
    private $auth;
    
    public function __construct() {
        $this->auth = new Auth();
    }
    
    public function addRoute($pattern, $handler) {
        $this->routes[$pattern] = $handler;
    }
    
    public function dispatch($url) {
        foreach ($this->routes as $pattern => $handler) {
            if (preg_match('#^' . $pattern . '$#', $url, $matches)) {
                array_shift($matches); // Remove a correspondência completa
                $this->callHandler($handler, $matches);
                return;
            }
        }
        
        // Rota não encontrada
        $this->show404();
    }
    
    private function callHandler($handler, $params = []) {
        switch ($handler) {
            case 'login':
                $this->handleLogin();
                break;
            case 'logout':
                $this->handleLogout();
                break;
            case 'dashboard':
                $this->handleDashboard();
                break;
            case 'landing':
                $this->handleLanding();
                break;
            case 'upsell':
                $this->handleUpsell();
                break;
            case 'downsell':
                $this->handleDownsell();
                break;
            case 'obrigado':
                $this->handleObrigado();
                break;
            case 'debug-dashboard':
                $this->handleDebugDashboard();
                break;
            case 'teste-dashboard':
                $this->handleTesteDashboard();
                break;
            case 'dashboard-funcionando':
                $this->handleDashboardFuncionando();
                break;
            case 'dashboard-simples':
                $this->handleDashboardSimples();
                break;
            case 'verificar-arquivos':
                $this->handleVerificarArquivos();
                break;
            case 'ebook':
                $this->handleEbook($params[0] ?? null);
                break;
            case 'video':
                $this->handleVideo($params[0] ?? null);
                break;
            case 'audio':
                $this->handleAudio($params[0] ?? null);
                break;
            case 'download':
                $this->handleDownload($params[0] ?? null);
                break;
            case 'grant-access':
                $this->handleGrantAccess();
                break;
            default:
                $this->show404();
        }
    }
    
    private function handleLogin() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $senha = $_POST['senha'] ?? '';
            
            if ($this->auth->login($email, $senha)) {
                header('Location: ' . DASHBOARD_URL);
                exit;
            } else {
                $error = 'Email ou senha incorretos';
            }
        }
        
        include VIEWS_PATH . '/login.php';
    }
    
    private function handleLogout() {
        $this->auth->logout();
        header('Location: ' . LOGIN_URL);
        exit;
    }
    
    private function handleDashboard() {
        $this->auth->requireLogin();
        
        $user = $this->auth->getCurrentUser();
        $materials = $this->auth->getUserMaterials($user['id']);
        
        include VIEWS_PATH . '/dashboard.php';
    }
    
    private function handleLanding() {
        // Exibe a landing page sem autenticação
        $landingFile = ROOT_PATH . '/libido-renovada.html';
        
        if (file_exists($landingFile)) {
            include $landingFile;
        } else {
            $this->show404();
        }
    }
    
    private function handleUpsell() {
        // Exibe a página de upsell sem autenticação
        $upsellFile = ROOT_PATH . '/libido-renovada-up.html';
        
        if (file_exists($upsellFile)) {
            include $upsellFile;
        } else {
            $this->show404();
        }
    }
    
    private function handleDownsell() {
        // Exibe a página de downsell sem autenticação
        $downsellFile = ROOT_PATH . '/libido-renovada-down.html';
        
        if (file_exists($downsellFile)) {
            include $downsellFile;
        } else {
            $this->show404();
        }
    }
    
    private function handleObrigado() {
        // Exibe a página de obrigado sem autenticação
        $obrigadoFile = ROOT_PATH . '/libido-renovada-obrigado.html';
        
        if (file_exists($obrigadoFile)) {
            include $obrigadoFile;
        } else {
            $this->show404();
        }
    }
    
    private function handleEbook($id) {
        $this->auth->requireLogin();
        
        if (!$id) {
            $this->show404();
            return;
        }
        
        $user = $this->auth->getCurrentUser();
        $material = $this->getUserMaterial($user['id'], $id, 'ebook');
        
        if (!$material) {
            $this->show404();
            return;
        }
        
        include VIEWS_PATH . '/ebook.php';
    }
    
    private function handleVideo($id) {
        $this->auth->requireLogin();
        
        if (!$id) {
            $this->show404();
            return;
        }
        
        $user = $this->auth->getCurrentUser();
        $material = $this->getUserMaterial($user['id'], $id, 'video');
        
        if (!$material) {
            $this->show404();
            return;
        }
        
        include VIEWS_PATH . '/video.php';
    }
    
    private function handleAudio($id) {
        $this->auth->requireLogin();
        
        if (!$id) {
            $this->show404();
            return;
        }
        
        $user = $this->auth->getCurrentUser();
        $material = $this->getUserMaterial($user['id'], $id, 'audio');
        
        if (!$material) {
            $this->show404();
            return;
        }
        
        include VIEWS_PATH . '/audio.php';
    }
    
    private function handleDownload($id) {
        $this->auth->requireLogin();
        
        if (!$id) {
            $this->show404();
            return;
        }
        
        $user = $this->auth->getCurrentUser();
        $material = $this->getUserMaterial($user['id'], $id);
        
        if (!$material) {
            $this->show404();
            return;
        }
        
        // Verifica se passou de 7 dias
        $liberadoEm = new DateTime($material['liberado_em']);
        $agora = new DateTime();
        $diferenca = $agora->diff($liberadoEm);
        
        if ($diferenca->days < 7) {
            header('Location: ' . DASHBOARD_URL);
            exit;
        }
        
        $this->serveFile($material['caminho'], $material['titulo']);
    }
    
    private function getUserMaterial($userId, $materialId, $tipo = null) {
        $db = Database::getInstance();
        
        $sql = "SELECT m.*, um.liberado_em 
                FROM materials m 
                INNER JOIN user_materials um ON m.id = um.material_id 
                WHERE um.user_id = ? AND m.id = ?";
        
        $params = [$userId, $materialId];
        
        if ($tipo) {
            $sql .= " AND m.tipo = ?";
            $params[] = $tipo;
        }
        
        return $db->fetch($sql, $params);
    }
    
    private function serveFile($caminho, $titulo) {
        $filePath = STORAGE_PATH . '/' . $caminho;
        
        if (!file_exists($filePath)) {
            $this->show404();
            return;
        }
        
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'mp4' => 'video/mp4',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav'
        ];
        
        $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
        
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $titulo . '.' . $extension . '"');
        header('Content-Length: ' . filesize($filePath));
        
        readfile($filePath);
        exit;
    }

    private function handleGrantAccess() {
        header('Content-Type: application/json');
        // Protegido por token simples de uso interno (aceita múltiplas formas)
        $queryOrBodyToken = $_GET['token'] ?? $_POST['token'] ?? '';
        $headerAuth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $headerXToken = $_SERVER['HTTP_X_HOTMART_TOKEN'] ?? '';

        $bearer = '';
        if ($headerAuth && preg_match('/Bearer\s+(.*)/i', $headerAuth, $m)) {
            $bearer = trim($m[1]);
        }

        $providedToken = $queryOrBodyToken ?: $headerXToken ?: $bearer;
        $validTokens = array_filter([
            defined('INTERNAL_API_TOKEN') ? INTERNAL_API_TOKEN : null,
            defined('HOTMART_WEBHOOK_TOKEN') ? HOTMART_WEBHOOK_TOKEN : null,
        ]);

        $authorized = false;
        foreach ($validTokens as $valid) {
            if (!empty($providedToken) && hash_equals((string)$valid, (string)$providedToken)) {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode([
                'status' => 'ready',
                'usage' => 'POST email=... hotmart_product_id=... [item_type=main|order_bump|upsell|downsell|bonus] [transaction=...]'
            ]);
            return;
        }

        $email = $_POST['email'] ?? '';
        $hotmartProductId = $_POST['hotmart_product_id'] ?? '';
        $itemType = $_POST['item_type'] ?? 'main';
        $transaction = $_POST['transaction'] ?? ('MANUAL_' . date('YmdHis'));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || empty($hotmartProductId)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Parâmetros inválidos']);
            return;
        }

        $db = null;
        $auth = new Auth();

        try {
            $db = Database::getInstance();
            $accessControl = new AccessControl();
            $db->beginTransaction();

            $user = $db->fetch("SELECT * FROM users WHERE email = ?", [$email]);
            if (!$user) {
                $userData = $auth->createUser($email, 'Cliente', null);
                if (!$userData) {
                    throw new Exception('Falha ao criar usuário');
                }
                $user = $userData;
            }

            // Mapeia material, se existir
            $material = $accessControl->getMaterialByProductId($hotmartProductId);
            $materialId = $material['id'] ?? null;
            $itemName = $material['titulo'] ?? ('Produto ' . $hotmartProductId);

            // Registra compra/liberação (idempotente)
            $accessControl->addUserPurchase($user['id'], $hotmartProductId, $transaction, $itemType, $itemName, $materialId);

            $db->commit();
            echo json_encode([
                'status' => 'success',
                'user_id' => $user['id'],
                'material_id' => $materialId,
                'item_type' => $itemType,
                'transaction' => $transaction
            ]);
        } catch (Throwable $e) {
            if ($db) { try { $db->rollback(); } catch (Exception $ignore) {} }
            http_response_code(500);
            error_log('grant-access error: ' . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Erro interno', 'detail' => $e->getMessage()]);
        }
    }
    
    
    private function handleDebugDashboard() {
        $db = Database::getInstance();
        
        // Se não estiver logado, usar usuário padrão para debug
        if (!isset($_SESSION['user']['id'])) {
            $userId = 1; // Usar ID 1 (Lochayde Guerreiro) para debug
            echo "<h1>🔍 Debug do Dashboard (Modo Debug - Usuário ID: $userId)</h1>";
            echo "<p><strong>⚠️ Você não está logado. Usando usuário padrão para debug.</strong></p>";
        } else {
            $userId = $_SESSION['user']['id'];
            echo "<h1>🔍 Debug do Dashboard</h1>";
            echo "<h2>Usuário: " . $_SESSION['user']['nome'] . " (ID: $userId)</h2>";
        }

        // 1. Verificar se o usuário existe
        echo "<h3>1. Verificação do Usuário</h3>";
        $user = $db->fetch("SELECT * FROM users WHERE id = ?", [$userId]);
        if ($user) {
            echo "✅ Usuário encontrado: " . $user['nome'] . " (" . $user['email'] . ")<br>";
        } else {
            echo "❌ Usuário não encontrado<br>";
            exit;
        }

        // 2. Verificar user_purchases
        echo "<h3>2. Verificação de user_purchases</h3>";
        $purchases = $db->fetchAll("SELECT * FROM user_purchases WHERE user_id = ?", [$userId]);
        echo "Total de compras: " . count($purchases) . "<br>";
        if (!empty($purchases)) {
            echo "<table border='1'>";
            echo "<tr><th>ID</th><th>Product ID</th><th>Item Type</th><th>Status</th><th>Material ID</th></tr>";
            foreach ($purchases as $purchase) {
                echo "<tr>";
                echo "<td>" . $purchase['id'] . "</td>";
                echo "<td>" . $purchase['hotmart_product_id'] . "</td>";
                echo "<td>" . $purchase['item_type'] . "</td>";
                echo "<td>" . $purchase['status'] . "</td>";
                echo "<td>" . ($purchase['material_id'] ?? 'NULL') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "❌ Nenhuma compra encontrada<br>";
        }

        // 3. Verificar user_materials (sistema antigo)
        echo "<h3>3. Verificação de user_materials (sistema antigo)</h3>";
        $userMaterials = $db->fetchAll("SELECT * FROM user_materials WHERE user_id = ?", [$userId]);
        echo "Total de materiais no sistema antigo: " . count($userMaterials) . "<br>";
        if (!empty($userMaterials)) {
            echo "<table border='1'>";
            echo "<tr><th>ID</th><th>Material ID</th><th>Liberado em</th></tr>";
            foreach ($userMaterials as $um) {
                echo "<tr>";
                echo "<td>" . $um['id'] . "</td>";
                echo "<td>" . $um['material_id'] . "</td>";
                echo "<td>" . $um['liberado_em'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "❌ Nenhum material no sistema antigo<br>";
        }

        // 4. Testar a query do dashboard
        echo "<h3>4. Teste da Query do Dashboard</h3>";
        $dashboardQuery = "
            SELECT DISTINCT m.*, up.purchase_date, up.item_type
            FROM user_purchases up
            LEFT JOIN product_material_mapping pmm ON up.hotmart_product_id = pmm.hotmart_product_id
            LEFT JOIN materials m ON pmm.material_id = m.id
            WHERE up.user_id = ? AND up.status = 'active' AND m.id IS NOT NULL
            ORDER BY up.purchase_date DESC
        ";

        $dashboardMaterials = $db->fetchAll($dashboardQuery, [$userId]);
        echo "Materiais encontrados pela query do dashboard: " . count($dashboardMaterials) . "<br>";

        if (!empty($dashboardMaterials)) {
            echo "<table border='1'>";
            echo "<tr><th>ID</th><th>Título</th><th>Tipo</th><th>Item Type</th><th>Purchase Date</th></tr>";
            foreach ($dashboardMaterials as $material) {
                echo "<tr>";
                echo "<td>" . $material['id'] . "</td>";
                echo "<td>" . $material['titulo'] . "</td>";
                echo "<td>" . $material['tipo'] . "</td>";
                echo "<td>" . $material['item_type'] . "</td>";
                echo "<td>" . $material['purchase_date'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "❌ Nenhum material encontrado pela query do dashboard<br>";
        }

        // 5. Testar query do sistema antigo
        echo "<h3>5. Teste da Query do Sistema Antigo</h3>";
        $legacyQuery = "
            SELECT m.*, um.liberado_em as purchase_date, 'legacy' as item_type
            FROM user_materials um
            JOIN materials m ON um.material_id = m.id
            WHERE um.user_id = ?
            ORDER BY um.liberado_em DESC
        ";

        $legacyMaterials = $db->fetchAll($legacyQuery, [$userId]);
        echo "Materiais encontrados pelo sistema antigo: " . count($legacyMaterials) . "<br>";

        if (!empty($legacyMaterials)) {
            echo "<table border='1'>";
            echo "<tr><th>ID</th><th>Título</th><th>Tipo</th><th>Item Type</th><th>Purchase Date</th></tr>";
            foreach ($legacyMaterials as $material) {
                echo "<tr>";
                echo "<td>" . $material['id'] . "</td>";
                echo "<td>" . $material['titulo'] . "</td>";
                echo "<td>" . $material['tipo'] . "</td>";
                echo "<td>" . $material['item_type'] . "</td>";
                echo "<td>" . $material['purchase_date'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "❌ Nenhum material encontrado pelo sistema antigo<br>";
        }

        echo "<h3>🎯 Conclusão</h3>";
        if (count($dashboardMaterials) > 0) {
            echo "✅ Sistema novo funcionando - " . count($dashboardMaterials) . " materiais encontrados<br>";
        } elseif (count($legacyMaterials) > 0) {
            echo "⚠️ Usando sistema antigo - " . count($legacyMaterials) . " materiais encontrados<br>";
        } else {
            echo "❌ Nenhum material encontrado em nenhum sistema<br>";
            echo "💡 Solução: Execute o script SQL para adicionar materiais<br>";
        }

        echo "<br><a href='" . BASE_URL . "/dashboard'>← Voltar ao Dashboard</a>";
    }

    private function handleTesteDashboard() {
        // Se não estiver logado, usar usuário padrão para teste
        if (!isset($_SESSION['user']['id'])) {
            $userId = 1; // Usar ID 1 (Lochayde Guerreiro) para teste
            $userName = 'Lochayde Guerreiro (Modo Teste)';
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

        ?>
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Teste Dashboard - Eros Vitta</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .material-card { border: 1px solid #ccc; padding: 15px; margin: 10px 0; border-radius: 5px; }
                .material-card h3 { color: #333; margin: 0 0 10px 0; }
                .material-card p { margin: 5px 0; color: #666; }
                .btn { display: inline-block; padding: 8px 16px; background: #007cba; color: white; text-decoration: none; border-radius: 3px; margin: 5px 5px 5px 0; }
                .btn:hover { background: #005a87; }
                .empty-state { text-align: center; padding: 40px; color: #666; }
                .debug-info { background: #f0f0f0; padding: 10px; margin: 10px 0; border-radius: 3px; font-size: 12px; }
            </style>
        </head>
        <body>
            <h1>🧪 Teste Dashboard Simples</h1>
            
            <div class="debug-info">
                <strong>Debug Info:</strong><br>
                Usuário ID: <?= $userId ?><br>
                Total de materiais encontrados: <?= count($materials) ?><br>
                Sessão ativa: <?= isset($_SESSION['user']) ? 'Sim' : 'Não' ?><br>
                Nome do usuário: <?= $userName ?>
            </div>

            <h2>Materiais do Usuário</h2>
            
            <?php if (empty($materials)): ?>
                <div class="empty-state">
                    <h3>❌ Nenhum material disponível</h3>
                    <p>Você ainda não possui materiais liberados.</p>
                </div>
            <?php else: ?>
                <div class="materials-list">
                    <?php foreach ($materials as $material): ?>
                        <div class="material-card">
                            <h3><?= htmlspecialchars($material['titulo']) ?></h3>
                            <p><strong>Tipo:</strong> <?= ucfirst($material['tipo']) ?></p>
                            <p><strong>Item Type:</strong> <?= $material['item_type'] ?></p>
                            <p><strong>Data de Compra:</strong> <?= date('d/m/Y H:i', strtotime($material['purchase_date'])) ?></p>
                            <p><strong>Caminho:</strong> <?= htmlspecialchars($material['caminho']) ?></p>
                            
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
                                        <a href="<?= BASE_URL ?>/download/<?= $material['id'] ?>" class="btn">
                                            📥 Download PDF
                                        </a>
                                    <?php else: ?>
                                        <span style="color: #999;">
                                            🔒 Download liberado em <?= $diasRestantes ?> dias
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <hr>
            <p><a href="<?= BASE_URL ?>/dashboard">← Voltar ao Dashboard Original</a></p>
            <p><a href="<?= BASE_URL ?>/debug-dashboard">🔍 Ver Debug Completo</a></p>
        </body>
        </html>
        <?php
    }

    private function handleDashboardFuncionando() {
        // Verificar se o usuário está logado
        if (!isset($_SESSION['user']['id'])) {
            header('Location: ' . LOGIN_URL);
            exit;
        }

        $userId = $_SESSION['user']['id'];
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

        $pageTitle = 'Eros Vitta Members';
        $currentPage = 'dashboard';
        include VIEWS_PATH . '/header.php';
        include VIEWS_PATH . '/sidebar.php';
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
                                        <?php if (isset($material['purchase_date'])): ?>
                                            Comprado em: <?= date('d/m/Y H:i', strtotime($material['purchase_date'])) ?>
                                        <?php else: ?>
                                            Liberado em: <?= date('d/m/Y H:i', strtotime($material['liberado_em'])) ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="material-actions">
                                    <!-- Visualização sempre liberada -->
                                    <a href="<?= BASE_URL ?>/<?= $material['tipo'] ?>/<?= $material['id'] ?>"
                                       class="btn btn-primary">
                                        <i class="fas fa-eye"></i>
                                        📖 Visualizar
                                    </a>

                                    <!-- Download com controle de tempo -->
                                    <?php if ($material['tipo'] === 'ebook'): ?>
                                        <?php
                                        // Usar data de compra se disponível, senão usar data de liberação
                                        $dataReferencia = isset($material['purchase_date']) ? $material['purchase_date'] : $material['liberado_em'];
                                        $dataRef = new DateTime($dataReferencia);
                                        $agora = new DateTime();
                                        $diferenca = $agora->diff($dataRef);

                                        if ($diferenca->days >= 7): ?>
                                            <a href="<?= BASE_URL ?>/download/<?= $material['id'] ?>"
                                               class="btn btn-secondary">
                                                <i class="fas fa-download"></i>
                                                📥 Download PDF
                                            </a>
                                        <?php else: ?>
                                            <div class="download-locked">
                                                🔒 Download liberado em <?= 7 - $diferenca->days ?> dias
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

        <?php include VIEWS_PATH . '/footer.php'; ?>
        <?php
    }

    private function handleDashboardSimples() {
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
        <?php
    }

    private function handleVerificarArquivos() {
        echo "<h1>🔍 Verificação de Arquivos na Pasta Storage</h1>";

        // Lista de arquivos esperados
        $arquivos = [
            'ebooks/o-guia-dos-5-toques-magicos.html',
            'ebooks/libido-renovada.html', 
            'ebooks/sem-desejo-nunca-mais.html',
            'ebooks/o-segredo-da-resistencia.html',
            'audios/libido-renovada-o-plano-de-acao-de-21-dias-para-reacender-a-intimidade-e-a-libido.mp3',
            'pdfs/guia-5-toques-magicos.pdf',
            'pdfs/libido-renovada.pdf',
            'pdfs/o-segredo-da-resistencia-o-guia-pratico-para-urar-mais-tempo-na-cama.pdf',
            'pdfs/sem-desejo-nunca-mais.pdf'
        ];

        echo "<h2>📁 Verificação de Arquivos:</h2>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
        echo "<tr style='background: #f0f0f0;'><th>Arquivo</th><th>Status</th><th>Tamanho</th></tr>";

        foreach ($arquivos as $arquivo) {
            $caminhoCompleto = STORAGE_PATH . '/' . $arquivo;
            $existe = file_exists($caminhoCompleto);
            $tamanho = $existe ? filesize($caminhoCompleto) : 0;
            
            echo "<tr>";
            echo "<td style='padding: 8px;'>" . $arquivo . "</td>";
            echo "<td style='padding: 8px;'>" . ($existe ? "✅ Existe" : "❌ Não existe") . "</td>";
            echo "<td style='padding: 8px;'>" . ($existe ? number_format($tamanho / 1024, 2) . " KB" : "-") . "</td>";
            echo "</tr>";
        }

        echo "</table>";

        echo "<h2>📋 Caminhos Corretos para o Banco:</h2>";
        echo "<ul>";
        foreach ($arquivos as $arquivo) {
            echo "<li><code>" . $arquivo . "</code></li>";
        }
        echo "</ul>";

        echo "<h2>🔧 Script SQL para Corrigir:</h2>";
        echo "<pre style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>";
        echo "-- Corrigir caminhos dos materiais\n";
        echo "UPDATE materials SET caminho = 'ebooks/o-guia-dos-5-toques-magicos.html' WHERE id = 6;\n";
        echo "UPDATE materials SET caminho = 'ebooks/libido-renovada.html' WHERE id = 7;\n";
        echo "UPDATE materials SET caminho = 'ebooks/sem-desejo-nunca-mais.html' WHERE id = 8;\n";
        echo "UPDATE materials SET caminho = 'ebooks/o-segredo-da-resistencia.html' WHERE id = 9;\n";
        echo "UPDATE materials SET caminho = 'audios/libido-renovada-o-plano-de-acao-de-21-dias-para-reacender-a-intimidade-e-a-libido.mp3' WHERE id = 10;\n";
        echo "</pre>";

        echo "<hr style='margin: 30px 0;'>";
        echo "<div style='text-align: center;'>";
        echo "<p><a href='" . BASE_URL . "/dashboard' style='color: #c67b54; margin: 0 10px;'>← Voltar ao Dashboard</a></p>";
        echo "</div>";
    }


    private function show404() {
        http_response_code(404);
        include VIEWS_PATH . '/404.php';
    }
}
?>
