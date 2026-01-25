<?php

namespace App\Auth\AuthManager;

trait ClearTokenCookiesTrait {

    public function clearTokenCookies(): void
    {
        setcookie('access_token', '', ['expires' => time() - 3600, 'path' => '/']);
        setcookie('refresh_token', '', ['expires' => time() - 3600, 'path' => '/']);
    }
}

?>