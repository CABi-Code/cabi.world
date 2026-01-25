<?php

namespace App\Auth\AuthManager;

trait SetTokenCookiesTrait {

    public function setTokenCookies(array $tokens): void
    {
        $opts = [
            'path' => '/',
            'secure' => $this->config['session']['secure'],
            'httponly' => $this->config['session']['httponly'],
            'samesite' => $this->config['session']['samesite']
        ];

        setcookie('access_token', $tokens['access_token'], 
            ['expires' => time() + $this->config['jwt_access_lifetime']] + $opts);
        setcookie('refresh_token', $tokens['refresh_token'], 
            ['expires' => time() + $this->config['jwt_refresh_lifetime']] + $opts);
    }
}

?>