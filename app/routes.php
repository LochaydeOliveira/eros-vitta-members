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
    
    private function show404() {
        http_response_code(404);
        include VIEWS_PATH . '/404.php';
    }
}
?>
