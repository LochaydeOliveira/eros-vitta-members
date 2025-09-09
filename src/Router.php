<?php
declare(strict_types=1);

namespace App;

use App\Http\JsonResponse;

class Router
{
    /** @var array<string, array<string, callable>> */
    private array $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'DELETE' => [],
        'PATCH' => [],
    ];

    public function get(string $path, callable $handler): void { $this->routes['GET'][$path] = $handler; }
    public function post(string $path, callable $handler): void { $this->routes['POST'][$path] = $handler; }
    public function put(string $path, callable $handler): void { $this->routes['PUT'][$path] = $handler; }
    public function delete(string $path, callable $handler): void { $this->routes['DELETE'][$path] = $handler; }
    public function patch(string $path, callable $handler): void { $this->routes['PATCH'][$path] = $handler; }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        $handler = $this->routes[$method][$uri] ?? null;

        if (!$handler) {
            JsonResponse::error('Rota não encontrada', 404);
            return;
        }

        try {
            $raw = file_get_contents('php://input') ?: '';
            // Armazena o corpo bruto para uso em validações (ex.: HMAC do webhook)
            $GLOBALS['__RAW_BODY__'] = $raw;
            $result = $handler($this->decodeBody($raw), $_REQUEST);
            if ($result !== null) {
                JsonResponse::ok($result);
            }
        } catch (\Throwable $e) {
            JsonResponse::error('Erro interno', 500, [
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return array<string,mixed>
     */
    private function decodeBody(string $raw): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            $data = json_decode($raw, true);
            return is_array($data) ? $data : [];
        }
        return $_POST ?: [];
    }
}
