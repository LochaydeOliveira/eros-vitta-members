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
            
            $userId = (int)$payload['sub'];
            
            // Verificar se usuário tem acessos bloqueados por reembolso
            $pdo = \App\Database::pdo();
            $stmt = $pdo->prepare('SELECT COUNT(*) as total FROM acessos WHERE usuario_id = ? AND status = "bloqueado" AND motivo_bloqueio = "webhook_reembolso"');
            $stmt->execute([$userId]);
            $blockedAccess = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($blockedAccess && (int)$blockedAccess['total'] > 0) {
                JsonResponse::error('Acesso bloqueado devido a reembolso', 403);
                return null;
            }
            
            // injeta user_id no request
            $request['user_id'] = $userId;
            return $next($body, $request);
        };
    }
}
