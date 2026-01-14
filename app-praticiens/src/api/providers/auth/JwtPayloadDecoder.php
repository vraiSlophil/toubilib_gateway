<?php

namespace toubilib\api\providers\auth;

final class JwtPayloadDecoder
{
    public function decode(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        $payloadJson = $this->base64UrlDecode($parts[1]);
        if ($payloadJson === null) {
            return null;
        }

        $payload = json_decode($payloadJson, true);
        if (!is_array($payload)) {
            return null;
        }

        return $payload;
    }

    private function base64UrlDecode(string $data): ?string
    {
        $data = strtr($data, '-_', '+/');
        $padding = strlen($data) % 4;
        if ($padding > 0) {
            $data .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($data, true);
        if ($decoded === false) {
            return null;
        }

        return $decoded;
    }
}
