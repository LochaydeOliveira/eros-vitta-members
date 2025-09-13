<?php
// Configurações locais do Eros Vitta Members
// NÃO COMMITAR ESTE ARQUIVO - adicionar ao .gitignore

return [
    // Banco de dados
    'DB_HOST' => 'localhost',
    'DB_PORT' => '3306',
    'DB_NAME' => 'paymen58_eros_vitta_members',
    'DB_USER' => 'SEU_USUARIO_DB',
    'DB_PASS' => 'SUA_SENHA_DB',
    
    // Aplicação
    'APP_KEY' => '45c2fea14e8647080a1f1e1fa489e6c9df49a8b36a94cf108550d9848f628ae9',
    'APP_URL' => 'https://erosvitta.com.br',
    
    // SMTP Zoho
    'SMTP_HOST' => 'smtp.zoho.com',
    'SMTP_PORT' => '465',
    'SMTP_SECURE' => 'ssl',
    'SMTP_USER' => 'contato@erosvitta.com.br',
    'SMTP_PASS' => 'SUA_SENHA_ZOHO',
    'SMTP_FROM' => 'contato@erosvitta.com.br',
    'SMTP_FROM_NAME' => 'Eros Vitta',
    
    // Hotmart
    'HOTMART_HOTTOK' => 'JXw991Om7EB7yY0Xf8ptOB4FJMlQaP534873',
    
    // Meta Conversions API
    'META_PIXEL_ID' => 'SEU_PIXEL_ID_AQUI',
    'META_ACCESS_TOKEN' => 'SEU_ACCESS_TOKEN_AQUI',
];