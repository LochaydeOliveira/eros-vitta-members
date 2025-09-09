<?php
declare(strict_types=1);

namespace App\Http;

final class JsonResponse
{
    /**
     * @param array<string,mixed> $data
     */
    public static function ok(array $data = [], int $status = 200): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code($status);
        }
        echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param array<string,mixed> $meta
     */
    public static function error(string $message, int $status = 400, array $meta = []): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code($status);
        }
        echo json_encode(['success' => false, 'error' => $message, 'meta' => $meta], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
