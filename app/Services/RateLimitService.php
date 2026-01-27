<?php

declare(strict_types=1);

namespace App\Services;

class RateLimitService
{
    private string $storagePath;

    public function __construct()
    {
        $this->storagePath = ROOT_PATH . '/storage/cache';
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }

    public function check(string $key, int $maxAttempts, int $windowSeconds): bool
    {
        $file = $this->storagePath . '/rate_limit_' . md5($key) . '.json';
        $data = file_exists($file) ? json_decode(file_get_contents($file), true) : ['count' => 0, 'reset' => time() + $windowSeconds];
        
        if (time() > $data['reset']) {
            $data = ['count' => 0, 'reset' => time() + $windowSeconds];
        }
        
        $data['count']++;
        file_put_contents($file, json_encode($data));
        
        return $data['count'] <= $maxAttempts;
    }

    public function reset(string $key): void
    {
        $file = $this->storagePath . '/rate_limit_' . md5($key) . '.json';
        if (file_exists($file)) {
            unlink($file);
        }
    }
}
