<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config;
use App\Http\JsonResponse;
use App\Security\RateLimiter;

final class MetaConversionsController
{
    private const META_API_URL = 'https://graph.facebook.com/v18.0/';
    
    /**
     * Envia evento de conversão para a Meta
     * POST /api/meta/conversion
     */
    public static function sendConversion(array $body): void
    {
        // Rate limiting
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $limiter = new RateLimiter('meta_conversion:' . $ip, 100, 3600); // 100 req/hora
        if (!$limiter->allow()) {
            JsonResponse::error('Rate limit excedido', 429);
            return;
        }

        $pixelId = Config::metaPixelId();
        $accessToken = Config::metaAccessToken();
        
        if (!$pixelId || !$accessToken) {
            JsonResponse::error('Meta API não configurada', 500);
            return;
        }

        $eventData = self::buildEventData($body);
        if (!$eventData) {
            JsonResponse::error('Dados do evento inválidos', 400);
            return;
        }

        $response = self::sendToMeta($pixelId, $accessToken, $eventData);
        
        if ($response['success']) {
            JsonResponse::ok(['message' => 'Evento enviado com sucesso', 'event_id' => $response['event_id']]);
        } else {
            JsonResponse::error('Falha ao enviar evento: ' . $response['error'], 400);
        }
    }

    /**
     * Envia evento de compra (Purchase) - chamado pelo webhook
     */
    public static function sendPurchaseEvent(array $purchaseData): bool
    {
        $pixelId = Config::metaPixelId();
        $accessToken = Config::metaAccessToken();
        
        if (!$pixelId || !$accessToken) {
            return false;
        }

        $eventData = [
            'data' => [
                [
                    'event_name' => 'Purchase',
                    'event_time' => time(),
                    'user_data' => [
                        'em' => hash('sha256', strtolower($purchaseData['email'] ?? '')),
                        'ph' => hash('sha256', preg_replace('/[^0-9]/', '', $purchaseData['phone'] ?? '')),
                        'fn' => hash('sha256', strtolower($purchaseData['first_name'] ?? '')),
                        'ln' => hash('sha256', strtolower($purchaseData['last_name'] ?? '')),
                        'ct' => hash('sha256', strtolower($purchaseData['city'] ?? '')),
                        'st' => hash('sha256', strtolower($purchaseData['state'] ?? '')),
                        'country' => hash('sha256', strtolower($purchaseData['country'] ?? '')),
                        'zp' => hash('sha256', $purchaseData['zip'] ?? '')
                    ],
                    'custom_data' => [
                        'value' => (float)($purchaseData['value'] ?? 0),
                        'currency' => 'BRL',
                        'content_name' => $purchaseData['product_name'] ?? 'Produto Digital',
                        'content_category' => 'Digital Product'
                    ],
                    'event_source_url' => Config::appUrl(),
                    'action_source' => 'website'
                ]
            ],
            'partner_agent' => 'ErosVitta-1.0'
        ];

        $response = self::sendToMeta($pixelId, $accessToken, $eventData);
        return $response['success'];
    }

    /**
     * Envia evento de visualização de página
     */
    public static function sendPageViewEvent(string $pageUrl, ?string $userEmail = null): bool
    {
        $pixelId = Config::metaPixelId();
        $accessToken = Config::metaAccessToken();
        
        if (!$pixelId || !$accessToken) {
            return false;
        }

        $eventData = [
            'data' => [
                [
                    'event_name' => 'PageView',
                    'event_time' => time(),
                    'event_source_url' => $pageUrl,
                    'action_source' => 'website',
                    'user_data' => $userEmail ? [
                        'em' => hash('sha256', strtolower($userEmail))
                    ] : [],
                ]
            ],
            'partner_agent' => 'ErosVitta-1.0'
        ];

        $response = self::sendToMeta($pixelId, $accessToken, $eventData);
        return $response['success'];
    }

    private static function buildEventData(array $body): ?array
    {
        $eventName = $body['event_name'] ?? '';
        $userData = $body['user_data'] ?? [];
        $customData = $body['custom_data'] ?? [];
        $eventTime = $body['event_time'] ?? time();
        $sourceUrl = $body['source_url'] ?? $_SERVER['HTTP_REFERER'] ?? Config::appUrl();

        // Validar evento obrigatório
        if (!$eventName) {
            return null;
        }

        // Processar dados do usuário (hash para privacidade)
        $hashedUserData = [];
        if (isset($userData['email'])) {
            $hashedUserData['em'] = hash('sha256', strtolower($userData['email']));
        }
        if (isset($userData['phone'])) {
            $hashedUserData['ph'] = hash('sha256', preg_replace('/[^0-9]/', '', $userData['phone']));
        }
        if (isset($userData['first_name'])) {
            $hashedUserData['fn'] = hash('sha256', strtolower($userData['first_name']));
        }
        if (isset($userData['last_name'])) {
            $hashedUserData['ln'] = hash('sha256', strtolower($userData['last_name']));
        }
        if (isset($userData['city'])) {
            $hashedUserData['ct'] = hash('sha256', strtolower($userData['city']));
        }
        if (isset($userData['state'])) {
            $hashedUserData['st'] = hash('sha256', strtolower($userData['state']));
        }
        if (isset($userData['country'])) {
            $hashedUserData['country'] = hash('sha256', strtolower($userData['country']));
        }
        if (isset($userData['zip'])) {
            $hashedUserData['zp'] = hash('sha256', $userData['zip']);
        }

        // Processar dados customizados
        $processedCustomData = [];
        if (isset($customData['value'])) {
            $processedCustomData['value'] = (float)$customData['value'];
        }
        if (isset($customData['currency'])) {
            $processedCustomData['currency'] = strtoupper($customData['currency']);
        }
        if (isset($customData['content_name'])) {
            $processedCustomData['content_name'] = $customData['content_name'];
        }
        if (isset($customData['content_category'])) {
            $processedCustomData['content_category'] = $customData['content_category'];
        }

        // Formato correto para Meta Conversions API
        return [
            'data' => [
                [
                    'event_name' => $eventName,
                    'event_time' => $eventTime,
                    'event_source_url' => $sourceUrl,
                    'action_source' => 'website',
                    'user_data' => $hashedUserData,
                    'custom_data' => $processedCustomData
                ]
            ],
            'partner_agent' => 'ErosVitta-1.0'
        ];
    }

    private static function sendToMeta(string $pixelId, string $accessToken, array $eventData): array
    {
        $url = self::META_API_URL . $pixelId . '/events';
        
        $postData = json_encode($eventData);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return ['success' => false, 'error' => 'cURL Error: ' . $error];
        }
        
        $responseData = json_decode($response, true);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'success' => true, 
                'event_id' => $responseData['events_received'] ?? null
            ];
        }
        
        return [
            'success' => false, 
            'error' => $responseData['error']['message'] ?? 'Erro desconhecido (HTTP ' . $httpCode . ')'
        ];
    }
}