<?php
/**
 * Script de teste para Meta Conversions API
 * Execute: php test-meta-api.php
 */

require_once 'src/Config.php';

echo "=== TESTE META CONVERSIONS API ===\n\n";

// Testar carregamento das configurações
echo "1. Testando configurações...\n";
$pixelId = \App\Config::metaPixelId();
$accessToken = \App\Config::metaAccessToken();

echo "   Pixel ID: " . ($pixelId ? $pixelId : 'NÃO CONFIGURADO') . "\n";
echo "   Access Token: " . ($accessToken ? substr($accessToken, 0, 20) . '...' : 'NÃO CONFIGURADO') . "\n";

if (!$pixelId || !$accessToken) {
    echo "   ❌ ERRO: Configurações da Meta não encontradas!\n";
    echo "   Verifique o arquivo config.local.php\n";
    exit(1);
}

echo "   ✅ Configurações carregadas com sucesso!\n\n";

// Testar endpoint da API
echo "2. Testando endpoint da API...\n";
$testData = [
    'event_name' => 'PageView',
    'user_data' => [
        'em' => hash('sha256', 'teste@exemplo.com'),
        'ph' => hash('sha256', '11999999999'),
        'fn' => hash('sha256', 'João'),
        'ln' => hash('sha256', 'Silva'),
        'ct' => hash('sha256', 'São Paulo'),
        'st' => hash('sha256', 'SP'),
        'country' => hash('sha256', 'BR'),
        'zp' => hash('sha256', '01234567')
    ],
    'custom_data' => [
        'content_name' => 'Teste de Integração',
        'content_category' => 'Digital Product',
        'value' => 97.00,
        'currency' => 'BRL'
    ],
    'event_time' => time(),
    'source_url' => 'https://erosvitta.com.br/teste'
];

$url = 'https://erosvitta.com.br/api/meta/conversion';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'User-Agent: ErosVitta-Test/1.0'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "   ❌ ERRO cURL: $error\n";
} else {
    echo "   Status HTTP: $httpCode\n";
    echo "   Resposta: " . substr($response, 0, 200) . "\n";
    
    if ($httpCode === 200) {
        echo "   ✅ API funcionando corretamente!\n";
    } else {
        echo "   ⚠️  API retornou erro. Verifique os logs do servidor.\n";
    }
}

echo "\n=== TESTE CONCLUÍDO ===\n";
