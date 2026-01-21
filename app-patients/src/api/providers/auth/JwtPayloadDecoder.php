<?php

namespace toubilib\api\providers\auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Throwable;

final class JwtPayloadDecoder
{
    public function decode(string $token): ?array
    {
        $secret = getenv('JWT_SECRET') ?: ($_ENV['JWT_SECRET'] ?? '');
        $algo = getenv('JWT_ALGORITHM') ?: ($_ENV['JWT_ALGORITHM'] ?? 'HS256');
        if ($secret === '') {
            return null;
        }

        try {
            $decoded = JWT::decode($token, new Key($secret, $algo));
        } catch (Throwable) {
            return null;
        }

        $payload = json_decode(json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), true);
        return is_array($payload) ? $payload : null;
    }
}
