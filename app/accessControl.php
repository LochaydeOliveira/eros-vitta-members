<?php
require_once 'config.php';
require_once 'db.php';

class AccessControl {
    private $db;
    
    public function __construct() {
        // Usar a conexão PDO diretamente para permitir ->prepare()
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Busca material por ID do produto da Hotmart
     */
    public function getMaterialByProductId($hotmartProductId) {
        $stmt = $this->db->prepare("
            SELECT m.*, pmm.material_type
            FROM product_material_mapping pmm
            JOIN materials m ON pmm.material_id = m.id
            WHERE pmm.hotmart_product_id = ?
        ");
        $stmt->execute([$hotmartProductId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Busca todos os materiais incluídos no Pacote Premium
     */
    public function getUpsellPackageMaterials($hotmartProductId) {
        $stmt = $this->db->prepare("
            SELECT m.*, upm.material_type
            FROM upsell_package_materials upm
            JOIN materials m ON upm.material_id = m.id
            WHERE upm.hotmart_product_id = ?
        ");
        $stmt->execute([$hotmartProductId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Busca todos os materiais comprados pelo usuário
     */
    public function getUserPurchasedMaterials($userId) {
        $stmt = $this->db->prepare("
            SELECT DISTINCT m.*, up.purchase_date, up.item_type, up.hotmart_product_id
            FROM user_purchases up
            LEFT JOIN product_material_mapping pmm ON up.hotmart_product_id = pmm.hotmart_product_id
            LEFT JOIN materials m ON pmm.material_id = m.id
            WHERE up.user_id = ? AND up.status = 'active'
            ORDER BY up.purchase_date DESC
        ");
        $stmt->execute([$userId]);
        $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Adicionar materiais do Pacote Premium se o usuário comprou
        $upsellMaterials = $this->getUserUpsellMaterials($userId);
        $materials = array_merge($materials, $upsellMaterials);
        
        // Remover duplicatas
        $uniqueMaterials = [];
        foreach ($materials as $material) {
            if (!isset($uniqueMaterials[$material['id']])) {
                $uniqueMaterials[$material['id']] = $material;
            }
        }
        
        return array_values($uniqueMaterials);
    }
    
    /**
     * Busca materiais do Pacote Premium se o usuário comprou
     */
    private function getUserUpsellMaterials($userId) {
        $stmt = $this->db->prepare("
            SELECT m.*, up.purchase_date, 'upsell' as item_type, up.hotmart_product_id
            FROM user_purchases up
            JOIN upsell_package_materials upm ON up.hotmart_product_id = upm.hotmart_product_id
            JOIN materials m ON upm.material_id = m.id
            WHERE up.user_id = ? AND up.status = 'active' AND up.item_type = 'upsell'
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Verifica se o usuário pode acessar um material específico
     */
    public function canAccessMaterial($userId, $materialId, $accessType = 'view') {
        $userMaterials = $this->getUserPurchasedMaterials($userId);
        
        // Verifica se o usuário tem acesso ao material
        $hasAccess = false;
        $purchaseDate = null;
        
        foreach ($userMaterials as $material) {
            if ($material['id'] == $materialId) {
                $hasAccess = true;
                $purchaseDate = $material['purchase_date'];
                break;
            }
        }
        
        if (!$hasAccess) {
            return false;
        }
        
        // Verifica período de carência para download de PDF
        if ($accessType === 'download' && $material['tipo'] === 'ebook') {
            $purchaseDate = new DateTime($purchaseDate);
            $now = new DateTime();
            $daysDiff = $now->diff($purchaseDate)->days;
            
            if ($daysDiff < 7) {
                return false; // Ainda não pode baixar
            }
        }
        
        return true;
    }
    
    /**
     * Calcula dias restantes para liberação do download
     */
    public function getDaysRemaining($purchaseDate) {
        $purchaseDate = new DateTime($purchaseDate);
        $now = new DateTime();
        $daysDiff = $now->diff($purchaseDate)->days;
        
        return max(0, 7 - $daysDiff);
    }
    
    /**
     * Adiciona compra do usuário
     */
    public function addUserPurchase($userId, $hotmartProductId, $transaction, $itemType, $itemName, $materialId = null) {
        // Idempotência: evita duplicidade pela mesma transação/produto/item/material
        if ($this->purchaseExists($userId, $transaction, $hotmartProductId, $itemType, $materialId)) {
            return true;
        }

        $stmt = $this->db->prepare("
            INSERT INTO user_purchases (user_id, hotmart_transaction, hotmart_product_id, item_type, item_name, material_id, purchase_date, status)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), 'active')
        ");
        return $stmt->execute([$userId, $transaction, $hotmartProductId, $itemType, $itemName, $materialId]);
    }
    
    /**
     * Processa compra do Pacote Premium (adiciona todos os materiais incluídos)
     */
    public function processUpsellPurchase($userId, $hotmartProductId, $transaction, $itemName) {
        // Adicionar compra principal do upsell
        $this->addUserPurchase($userId, $hotmartProductId, $transaction, 'upsell', $itemName);
        
        // Adicionar todos os materiais incluídos no pacote
        $packageMaterials = $this->getUpsellPackageMaterials($hotmartProductId);
        foreach ($packageMaterials as $material) {
            $this->addUserPurchase($userId, $hotmartProductId, $transaction, 'upsell', $itemName, $material['id']);
        }
        
        return true;
    }

    /**
     * Verifica se já existe um registro de compra para idempotência
     */
    private function purchaseExists($userId, $transaction, $hotmartProductId, $itemType, $materialId = null) {
        $stmt = $this->db->prepare("
            SELECT id FROM user_purchases
            WHERE user_id = ?
              AND hotmart_transaction = ?
              AND hotmart_product_id = ?
              AND item_type = ?
              AND ((? IS NULL AND material_id IS NULL) OR material_id = ?)
            LIMIT 1
        ");
        $stmt->execute([$userId, $transaction, $hotmartProductId, $itemType, $materialId, $materialId]);
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Marca compras como reembolsadas por transação
     */
    public function markRefundedByTransaction($transaction, $reason = null) {
        $stmt = $this->db->prepare("
            UPDATE user_purchases
               SET status = 'refunded', refund_reason = ?, refund_date = NOW()
             WHERE hotmart_transaction = ? AND status = 'active'
        ");
        return $stmt->execute([$reason, $transaction]);
    }

    /**
     * Marca compras como canceladas por transação (revoga acesso)
     */
    public function markCancelledByTransaction($transaction, $reason = null) {
        $stmt = $this->db->prepare("
            UPDATE user_purchases
               SET status = 'cancelled', refund_reason = COALESCE(?, refund_reason), refund_date = COALESCE(refund_date, NOW())
             WHERE hotmart_transaction = ? AND status = 'active'
        ");
        return $stmt->execute([$reason, $transaction]);
    }
}
?>
