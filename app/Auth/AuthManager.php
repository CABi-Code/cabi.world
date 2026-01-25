<?php

declare(strict_types=1);

namespace App\Auth;

use App\Core\Database;
use App\Repository\UserRepository;
use App\Repository\RefreshTokenRepository;

class AuthManager
{
	use \App\Auth\AuthManager\RegisterTrait;
	use \App\Auth\AuthManager\LoginTrait;
	use \App\Auth\AuthManager\GenerateTokensTrait;
	use \App\Auth\AuthManager\RefreshTrait;
	use \App\Auth\AuthManager\SetTokenCookiesTrait;
	use \App\Auth\AuthManager\LogoutLogoutAllLogAuthTrait;
	use \App\Auth\AuthManager\GetCurrentUserTrait;
	use \App\Auth\AuthManager\ClearTokenCookiesTrait;
	
    private JWT $jwt;
    private UserRepository $userRepo;
    private RefreshTokenRepository $tokenRepo;
    private array $config;

    public function __construct()
    {
        $this->config = require CONFIG_PATH . '/app.php';
        $this->jwt = new JWT($this->config['jwt_secret']);
        $this->userRepo = new UserRepository();
        $this->tokenRepo = new RefreshTokenRepository();
    }

}