<?php

declare(strict_types=1);

namespace App\Services;

class TurnstileService
{
    private string $secretKey;
    private bool $enabled;

    public function __construct()
    {
        $config = require CONFIG_PATH . '/app.php';
        $this->secretKey = $config['turnstile']['secret_key'];
        $this->enabled = $config['turnstile']['enabled'];
    }

    public function validate(?string $token, ?string $remoteIp = null): array
    {
        if (!$this->enabled) {
            return ['success' => true];
        }

        if (empty($token)) {
            return ['success' => false, 'error' => 'Подтвердите, что вы не робот'];
        }

        $response = $this->verify($token, $remoteIp);

        if (!$response['success']) {
            $errorCode = $response['error-codes'][0] ?? 'unknown';
            return ['success' => false, 'error' => $this->getErrorMessage($errorCode)];
        }

        return ['success' => true];
    }

    private function verify(string $token, ?string $remoteIp): array
    {
        $data = ['secret' => $this->secretKey, 'response' => $token];
        
        if ($remoteIp) {
            $data['remoteip'] = $remoteIp;
        }

        $context = stream_context_create([
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data),
                'timeout' => 10
            ]
        ]);

        $response = @file_get_contents(
            'https://challenges.cloudflare.com/turnstile/v0/siteverify',
            false,
            $context
        );

        if ($response === false) {
            return ['success' => false, 'error-codes' => ['connection-error']];
        }

        return json_decode($response, true) ?? ['success' => false, 'error-codes' => ['parse-error']];
    }

    private function getErrorMessage(string $code): string
    {
        return match($code) {
            'missing-input-secret' => 'Ошибка конфигурации сервера',
            'invalid-input-secret' => 'Ошибка конфигурации сервера',
            'missing-input-response' => 'Подтвердите, что вы не робот',
            'invalid-input-response' => 'Проверка не пройдена, попробуйте снова',
            'bad-request' => 'Некорректный запрос',
            'timeout-or-duplicate' => 'Время истекло, попробуйте снова',
            'internal-error' => 'Ошибка сервера проверки',
            'connection-error' => 'Не удалось связаться с сервером проверки',
            default => 'Ошибка проверки, попробуйте снова'
        };
    }
}