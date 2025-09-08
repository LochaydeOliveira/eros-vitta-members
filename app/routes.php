<?php
require_once 'auth.php';
require_once 'mailer.php';

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
    
    
    private function handleDebugDashboard() {
        // Verificar se o usuário está logado
        if (!isset($_SESSION['user']['id'])) {
            header('Location: ' . LOGIN_URL);
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $db = Database::getInstance();

        echo "<h1>🔍 Debug do Dashboard</h1>";
        echo "<h2>Usuário: " . $_SESSION['user']['nome'] . " (ID: $userId)</h2>";

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

    private function show404() {
        http_response_code(404);
        include VIEWS_PATH . '/404.php';
    }
}
?>
