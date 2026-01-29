<?php

declare(strict_types=1);

return [
    'name' => 'cabi.world',
    'debug' => (bool)($_ENV['APP_DEBUG'] ?? false),
    'url' => $_ENV['APP_URL'] ?? 'https://cabi.world',
    
    // JWT
    'jwt_secret' => $_ENV['JWT_SECRET'] ?? 'change-this-secret-key',
    'jwt_access_lifetime' => 900,
    'jwt_refresh_lifetime' => 2592000,
    
    // Rate limiting
    'login_max_attempts' => 5,
    'login_lockout_time' => 300,
    
    // Session
    'session' => [
        'lifetime' => 86400,
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ],
	
	// капча Cloudflare Turnstile
	'turnstile' => [
    'site_key' => $_ENV['TURNSTILE_SITE_KEY'] ?? '',
    'secret_key' => $_ENV['TURNSTILE_SECRET_KEY'] ?? '',
    'enabled' => (bool)($_ENV['TURNSTILE_ENABLED'] ?? true),
	],
	
	// Rate Limiting
	'rate_limit' => [
		// Глобальный лимит запросов
		'global' => [
			'requests' => 1,      // запросов
			'window' => 600,        // за секунд
			'block_duration' => 300, // блокировка на секунд
		],
		// Лимит для API
		'api' => [
			'requests' => 1,
			'window' => 600,
			'block_duration' => 300,
		],
		// Лимит для auth endpoints
		'auth' => [
			'requests' => 5,
			'window' => 60,
			'block_duration' => 600,
		],
		// После скольких превышений требовать капчу
		'captcha_threshold' => 3,
	],
    
    // Uploads
    'uploads' => [
        'avatars' => [
            'path' => '/uploads/avatars/',
            'max_size' => 10 * 1024 * 1024, // 5MB
            'sizes' => [
                'original' => null,
                'large' => 256,
                'medium' => 128,
                'small' => 64,
                'mini' => 32
            ]
        ],
        'banners' => [
            'path' => '/uploads/banners/',
            'max_size' => 10 * 1024 * 1024, // 10MB
            'width' => 1200,
            'height' => 300
        ]
    ],
    
    // API Keys
    'curseforge_api_key' => $_ENV['CURSEFORGE_API_KEY'] ?? ''
];
