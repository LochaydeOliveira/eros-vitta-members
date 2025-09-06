<?php
/**
 * Sistema de Validação - ValidaPro
 * Funções para validação e sanitização de dados
 */

// Validar e sanitizar email
function validateAndSanitizeEmail($email) {
    $email = trim($email);
    
    if (empty($email)) {
        return ['valid' => false, 'error' => 'Email é obrigatório'];
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['valid' => false, 'error' => 'Email inválido'];
    }
    
    return ['valid' => true, 'value' => strtolower($email)];
}

// Validar e sanitizar senha
function validateAndSanitizePassword($password, $confirm_password = null) {
    if (empty($password)) {
        return ['valid' => false, 'error' => 'Senha é obrigatória'];
    }
    
    $min_length = PASSWORD_MIN_LENGTH ?? 8;
    if (strlen($password) < $min_length) {
        return ['valid' => false, 'error' => "Senha deve ter pelo menos $min_length caracteres"];
    }
    
    if ($confirm_password !== null && $password !== $confirm_password) {
        return ['valid' => false, 'error' => 'Senhas não coincidem'];
    }
    
    return ['valid' => true, 'value' => $password];
}

// Validar e sanitizar nome
function validateAndSanitizeName($name) {
    $name = trim($name);
    
    if (empty($name)) {
        return ['valid' => false, 'error' => 'Nome é obrigatório'];
    }
    
    if (strlen($name) < 2) {
        return ['valid' => false, 'error' => 'Nome deve ter pelo menos 2 caracteres'];
    }
    
    if (strlen($name) > 255) {
        return ['valid' => false, 'error' => 'Nome muito longo'];
    }
    
    // Permitir apenas letras, espaços, hífens e acentos
    if (!preg_match('/^[a-zA-ZÀ-ÿ\s\-]+$/', $name)) {
        return ['valid' => false, 'error' => 'Nome contém caracteres inválidos'];
    }
    
    return ['valid' => true, 'value' => ucwords(strtolower($name))];
}

// Validar e sanitizar texto
function validateAndSanitizeText($text, $field_name, $max_length = 1000) {
    $text = trim($text);
    
    if (empty($text)) {
        return ['valid' => false, 'error' => "$field_name é obrigatório"];
    }
    
    if (strlen($text) > $max_length) {
        return ['valid' => false, 'error' => "$field_name muito longo (máximo $max_length caracteres)"];
    }
    
    return ['valid' => true, 'value' => htmlspecialchars($text, ENT_QUOTES, 'UTF-8')];
}

// Validar token CSRF
function validateCSRFToken($token) {
    if (empty($token)) {
        return ['valid' => false, 'error' => 'Token CSRF é obrigatório'];
    }
    
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return ['valid' => false, 'error' => 'Token CSRF inválido'];
    }
    
    return ['valid' => true];
}

// Validar array de checklist
function validateChecklist($checklist) {
    if (!is_array($checklist)) {
        return ['valid' => false, 'error' => 'Checklist inválido'];
    }
    
    $valid_items = [
        'vida_mais_facil', 'criativos_dinamicos', 'buscas_google', 
        'vendido_lojas', 'economiza_dinheiro', 'economiza_tempo',
        'nao_nicho_sensivel', 'menos_50_dolares', 'so_internet', 'nao_commodity'
    ];
    
    foreach ($checklist as $item) {
        if (!in_array($item, $valid_items)) {
            return ['valid' => false, 'error' => 'Item de checklist inválido'];
        }
    }
    
    return ['valid' => true, 'value' => $checklist];
}

// Validar ID numérico
function validateNumericId($id, $field_name = 'ID') {
    if (empty($id)) {
        return ['valid' => false, 'error' => "$field_name é obrigatório"];
    }
    
    if (!is_numeric($id) || $id <= 0) {
        return ['valid' => false, 'error' => "$field_name inválido"];
    }
    
    return ['valid' => true, 'value' => (int)$id];
}

// Sanitizar entrada geral
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Validar URL
function validateURL($url) {
    $url = trim($url);
    if (empty($url)) {
        return ['valid' => false, 'error' => 'URL é obrigatória'];
    }
    
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return ['valid' => false, 'error' => 'URL inválida'];
    }
    
    return ['valid' => true, 'value' => $url];
}

// Validar data
function validateDate($date, $format = 'Y-m-d H:i:s') {
    $date = trim($date);
    if (empty($date)) {
        return ['valid' => false, 'error' => 'Data é obrigatória'];
    }
    
    $d = DateTime::createFromFormat($format, $date);
    if (!$d || $d->format($format) !== $date) {
        return ['valid' => false, 'error' => 'Data inválida'];
    }
    
    return ['valid' => true, 'value' => $date];
}

// Função para validar múltiplos campos
function validateMultipleFields($fields) {
    $errors = [];
    $validated = [];
    
    foreach ($fields as $field_name => $field_data) {
        $validation_result = $field_data['validator']($field_data['value']);
        
        if (!$validation_result['valid']) {
            $errors[$field_name] = $validation_result['error'];
        } else {
            $validated[$field_name] = $validation_result['value'];
        }
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'validated' => $validated
    ];
} 