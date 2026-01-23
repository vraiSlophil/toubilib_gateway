<?php

namespace toubilib\api\providers\auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Throwable;

function env(string $key, mixed $default = null, ?callable $cast = null): mixed
{
    $value = getenv($key);
    if ($value === false) {
        $value = $default;
    }
    if ($cast !== null) {
        return $cast($value);
    }
    return $value;
}

final class JwtPayloadDecoder
{
    public function decode(string $token): ?array
    {
        $secret = env('JWT_SECRET', '');
        $algo = env('JWT_ALGORITHM', 'HS256');
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
