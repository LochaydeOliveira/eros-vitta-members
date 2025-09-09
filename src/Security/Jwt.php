<?php
declare(strict_types=1);

namespace App\Security;

use App\Config;

final class Jwt
{
    /**
     * @param array<string,mixed> $payload
     */
    public static function sign(array $payload, int $ttlSeconds = 86400): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $now = time();
        $payload['iat'] = $now;
        $payload['exp'] = $now + $ttlSeconds;
        $segments = [
            self::b64(json_encode($header)),
            self::b64(json_encode($payload)),
        ];
        $signingInput = implode('.', $segments);
        $signature = hash_hmac('sha256', $signingInput, Config::appKey(), true);
        $segments[] = self::b64($signature);
        return implode('.', $segments);
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function verify(string $jwt): ?array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) return null;
        [$h, $p, $s] = $parts;
        $signingInput = $h . '.' . $p;
        $expected = self::b64(hash_hmac('sha256', $signingInput, Config::appKey(), true));
        if (!hash_equals($expected, $s)) return null;
        $payload = json_decode(self::ub64($p), true);
        if (!is_array($payload)) return null;
        if (isset($payload['exp']) && time() >= (int)$payload['exp']) return null;
        return $payload;
    }

    private static function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function ub64(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/')) ?: '';
    }
}
