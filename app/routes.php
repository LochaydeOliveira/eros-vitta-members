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
            case 'debug-login':
                $this->handleDebugLogin();
                break;
            case 'simular-compra':
                $this->handleSimularCompra();
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
    
    private function handleDebugLogin() {
        echo "<h2>🔍 Debug do Sistema de Login</h2>";
        
        // 1. Verificar conexão com banco
        try {
            $db = Database::getInstance();
            echo "✅ Conexão com banco: OK<br>";
        } catch (Exception $e) {
            echo "❌ Erro na conexão: " . $e->getMessage() . "<br>";
            return;
        }
        
        // 2. Verificar se usuário existe
        $email = 'lochaydeguerreiro@hotmail.com';
        $user = $db->fetch("SELECT * FROM users WHERE email = ?", [$email]);
        
        if ($user) {
            echo "✅ Usuário encontrado:<br>";
            echo "ID: " . $user['id'] . "<br>";
            echo "Nome: " . $user['nome'] . "<br>";
            echo "Email: " . $user['email'] . "<br>";
            echo "Hash da senha: " . $user['senha'] . "<br>";
        } else {
            echo "❌ Usuário não encontrado!<br>";
            echo "Execute o script resetar-usuarios.sql<br>";
            return;
        }
        
        // 3. Testar senha
        $senha_teste = '12345';
        echo "<br><strong>🔐 Teste de Senha:</strong><br>";
        echo "Senha testada: " . $senha_teste . "<br>";
        
        $senha_correta = password_verify($senha_teste, $user['senha']);
        echo "Resultado password_verify: " . ($senha_correta ? "✅ CORRETA" : "❌ INCORRETA") . "<br>";
        
        // 4. Se senha estiver incorreta, gerar nova
        if (!$senha_correta) {
            echo "<br><strong>🔧 Corrigindo senha:</strong><br>";
            $nova_senha_hash = password_hash($senha_teste, PASSWORD_DEFAULT);
            echo "Novo hash: " . $nova_senha_hash . "<br>";
            
            $db->execute("UPDATE users SET senha = ? WHERE email = ?", [$nova_senha_hash, $email]);
            echo "✅ Senha atualizada no banco!<br>";
            
            // Testar novamente
            $senha_correta = password_verify($senha_teste, $nova_senha_hash);
            echo "Teste após correção: " . ($senha_correta ? "✅ CORRETA" : "❌ INCORRETA") . "<br>";
        }
        
        // 5. Testar login com Auth
        echo "<br><strong>🚀 Teste de Login com Auth:</strong><br>";
        $login_sucesso = $this->auth->login($email, $senha_teste);
        
        if ($login_sucesso) {
            echo "✅ Login realizado com sucesso!<br>";
            echo "👤 Sessão criada:<br>";
            echo "- user_id: " . ($_SESSION['user_id'] ?? 'NÃO DEFINIDO') . "<br>";
            echo "- user_email: " . ($_SESSION['user_email'] ?? 'NÃO DEFINIDO') . "<br>";
            echo "- user_nome: " . ($_SESSION['user_nome'] ?? 'NÃO DEFINIDO') . "<br>";
            echo "- login_time: " . ($_SESSION['login_time'] ?? 'NÃO DEFINIDO') . "<br>";
        } else {
            echo "❌ Falha no login!<br>";
        }
        
        // 6. Verificar se está logado
        echo "<br><strong>🔍 Verificação de Login:</strong><br>";
        $is_logged = $this->auth->isLoggedIn();
        echo "isLoggedIn(): " . ($is_logged ? "✅ SIM" : "❌ NÃO") . "<br>";
        
        echo "<br><strong>🎯 Próximos passos:</strong><br>";
        echo "1. Se tudo estiver ✅, tente fazer login em: <a href='https://erosvitta.com.br/login' target='_blank'>https://erosvitta.com.br/login</a><br>";
        echo "2. Use: lochaydeguerreiro@hotmail.com / 12345<br>";
        echo "3. Se ainda não funcionar, o problema pode estar no redirecionamento<br>";
    }
    
    private function handleSimularCompra() {
        echo "<h2>🛒 Simulador de Compra - Pacote Premium</h2>";
        
        // Verificar se usuário está logado
        if (!$this->auth->isLoggedIn()) {
            echo "❌ Você precisa estar logado para simular uma compra.<br>";
            echo "<a href='https://erosvitta.com.br/login'>Fazer Login</a><br>";
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $userEmail = $_SESSION['user_email'];
        
        echo "✅ Usuário logado: " . $_SESSION['user_nome'] . "<br>";
        echo "📧 Email: " . $userEmail . "<br>";
        
        // Simular compra do Pacote Premium
        echo "<br><strong>🛍️ Simulando compra do Pacote Premium:</strong><br>";
        
        try {
            $db = Database::getInstance();
            $db->beginTransaction();
            
            // 1. Produto Principal: Libido Renovada
            echo "📖 Adicionando: Libido Renovada (Produto Principal)<br>";
            $this->auth->addUserMaterial($userId, 1);
            
            // 2. Order Bump: 5 Toques Mágicos
            echo "🎁 Adicionando: 5 Toques Mágicos (Order Bump)<br>";
            $this->auth->addUserMaterial($userId, 6);
            
            // 3. Pacote Premium: Versão em Áudio
            echo "🎧 Adicionando: Versão em Áudio (Pacote Premium)<br>";
            $this->auth->addUserMaterial($userId, 7);
            
            // 4. Bônus 1: O Segredo da Resistência
            echo "📚 Adicionando: O Segredo da Resistência (Bônus)<br>";
            $this->auth->addUserMaterial($userId, 9);
            
            // 5. Bônus 2: Sem Desejo Nunca Mais
            echo "💎 Adicionando: Sem Desejo Nunca Mais (Bônus)<br>";
            $this->auth->addUserMaterial($userId, 8);
            
            $db->commit();
            
            echo "<br>✅ <strong>Compra simulada com sucesso!</strong><br>";
            echo "🎉 Você agora tem acesso a todos os materiais do Pacote Premium!<br>";
            
            echo "<br><strong>📚 Materiais liberados:</strong><br>";
            echo "1. 📖 Libido Renovada (Ebook Principal)<br>";
            echo "2. 🎁 5 Toques Mágicos (Order Bump)<br>";
            echo "3. 🎧 Versão em Áudio (Pacote Premium)<br>";
            echo "4. 📚 O Segredo da Resistência (Bônus)<br>";
            echo "5. 💎 Sem Desejo Nunca Mais (Bônus)<br>";
            
            echo "<br><strong>🎯 Próximos passos:</strong><br>";
            echo "1. <a href='https://erosvitta.com.br/dashboard' target='_blank'>Acessar Dashboard</a><br>";
            echo "2. Verificar todos os materiais liberados<br>";
            echo "3. Testar visualização e downloads<br>";
            
        } catch (Exception $e) {
            $db->rollback();
            echo "❌ Erro ao simular compra: " . $e->getMessage() . "<br>";
        }
    }
    
    private function show404() {
        http_response_code(404);
        include VIEWS_PATH . '/404.php';
    }
}
?>
