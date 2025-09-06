<?php
require_once 'db.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function login($email, $senha) {
        $user = $this->db->fetch(
            "SELECT * FROM users WHERE email = ?", 
            [$email]
        );
        
        if ($user && password_verify($senha, $user['senha'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_nome'] = $user['nome'];
            $_SESSION['login_time'] = time();
            return true;
        }
        
        return false;
    }
    
    public function logout() {
        session_destroy();
        session_start();
    }
    
    public function isLoggedIn() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        // Verifica timeout da sessão
        if (isset($_SESSION['login_time']) && 
            (time() - $_SESSION['login_time']) > SESSION_TIMEOUT) {
            $this->logout();
            return false;
        }
        
        return true;
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'email' => $_SESSION['user_email'],
            'nome' => $_SESSION['user_nome']
        ];
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: ' . LOGIN_URL);
            exit;
        }
    }
    
    public function createUser($email, $nome, $senha = null) {
        if ($senha === null) {
            $senha = $this->generatePassword();
        }
        
        $hashedPassword = password_hash($senha, PASSWORD_DEFAULT);
        
        try {
            $this->db->query(
                "INSERT INTO users (email, nome, senha) VALUES (?, ?, ?)",
                [$email, $nome, $hashedPassword]
            );
            
            return [
                'id' => $this->db->lastInsertId(),
                'email' => $email,
                'nome' => $nome,
                'senha' => $senha
            ];
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry
                return false;
            }
            throw $e;
        }
    }
    
    public function getUserMaterials($userId) {
        return $this->db->fetchAll(
            "SELECT m.*, um.liberado_em 
             FROM materials m 
             INNER JOIN user_materials um ON m.id = um.material_id 
             WHERE um.user_id = ? 
             ORDER BY um.liberado_em DESC",
            [$userId]
        );
    }
    
    public function addUserMaterial($userId, $materialId) {
        try {
            $this->db->query(
                "INSERT INTO user_materials (user_id, material_id) VALUES (?, ?)",
                [$userId, $materialId]
            );
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    private function generatePassword($length = 8) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        return substr(str_shuffle($chars), 0, $length);
    }
}
?>
