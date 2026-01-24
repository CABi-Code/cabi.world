<?php

$routes = [
    '/' => ['page' => 'home'],
    '/modrinth' => ['page' => 'modrinth'],
    '/curseforge' => ['page' => 'curseforge'],
    '/login' => ['page' => 'login', 'guest' => true],
    '/register' => ['page' => 'register', 'guest' => true],
    '/forgot-password' => ['page' => 'forgot-password', 'guest' => true],
    '/settings' => ['page' => 'settings', 'auth' => true],
    '/logout' => ['action' => 'logout'],
];

?>