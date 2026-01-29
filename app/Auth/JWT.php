<?php

declare(strict_types=1);

namespace App\Auth;

class JWT
{
    public function __construct(private string $secret) {}

    public function encode(array $payload): string
    {
        $header = $this->base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payload['iat'] = time();
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));
        $signature = $this->base64UrlEncode(hash_hmac('sha256', "$header.$payloadEncoded", $this->secret, true));
        return "$header.$payloadEncoded.$signature";
    }

	public function decode(string $token): ?array
	{
		$parts = explode('.', $token);
		if (count($parts) !== 3) return null;

		[$header, $payload, $signature] = $parts;
		$expectedSignature = $this->base64UrlEncode(hash_hmac('sha256', "$header.$payload", $this->secret, true));
		
		if (!hash_equals($expectedSignature, $signature)) return null;

		$data = json_decode($this->base64UrlDecode($payload), true);
		if (!$data) return null;
		
		// Проверка срока действия
		if (isset($data['exp']) && $data['exp'] < time()) return null;
		
		// Проверка обязательных полей
		if (!isset($data['user_id']) || !isset($data['type'])) return null;

		return $data;
	}

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
