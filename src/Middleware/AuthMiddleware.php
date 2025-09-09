<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Http\JsonResponse;
use App\Security\Jwt;

final class AuthMiddleware
{
    /**
     * @param callable $next function(array $body, array $request): mixed
     */
    public static function requireUser(callable $next): callable
    {
        return static function (array $body, array $request) use ($next) {
            $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            if (!preg_match('/^Bearer\s+(.*)$/i', $auth, $m)) {
                JsonResponse::error('Não autenticado', 401);
                return null;
            }
            $payload = Jwt::verify(trim($m[1]));
            if (!$payload || empty($payload['sub'])) {
                JsonResponse::error('Token inválido ou expirado', 401);
                return null;
            }
            // injeta user_id no request
            $request['user_id'] = (int)$payload['sub'];
            return $next($body, $request);
        };
    }
}
