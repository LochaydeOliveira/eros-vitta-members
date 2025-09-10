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
        // Log básico em arquivo (logs/api_YYYY-MM-DD.log)
        try {
            $dir = __DIR__ . '/../../logs';
            if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
            $line = sprintf(
                "%s\t%s\t%s\t%s\n",
                date('c'),
                (string)($_SERVER['REQUEST_METHOD'] ?? ''),
                (string)($_SERVER['REQUEST_URI'] ?? ''),
                $message . (empty($meta) ? '' : (' | meta=' . json_encode($meta)))
            );
            @file_put_contents($dir . '/api_' . date('Y-m-d') . '.log', $line, FILE_APPEND);
        } catch (\Throwable $e) { /* ignore */ }
        echo json_encode(['success' => false, 'error' => $message, 'meta' => $meta], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
