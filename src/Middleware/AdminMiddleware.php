<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Http\JsonResponse;
use App\Security\Jwt;

final class AdminMiddleware
{
    /**
     * @param callable $next function(array $body, array $request): mixed
     */
    public static function requireAdmin(callable $next): callable
    {
        return static function (array $body, array $request) use ($next) {
            $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            if (!preg_match('/^Bearer\s+(.*)$/i', $auth, $m)) {
                JsonResponse::error('Não autenticado', 401);
                return null;
            }
            $payload = Jwt::verify(trim($m[1]));
            if (!$payload || empty($payload['sub']) || ($payload['role'] ?? '') !== 'admin') {
                JsonResponse::error('Acesso negado', 403);
                return null;
            }
            $request['user_id'] = (int)$payload['sub'];
            $request['role'] = 'admin';
            return $next($body, $request);
        };
    }
}


