<?php

declare(strict_types=1);

use App\Http\Router;
use App\Controllers\Web\HomeController;
use App\Controllers\Web\AuthController;
use App\Controllers\Web\ProfileController;
use App\Controllers\Web\ModpackController;
use App\Controllers\Web\SettingsController;
use App\Controllers\Web\ChatController;
use App\Controllers\Web\AdminController;

// Главная страница
Router::get('/', [HomeController::class, 'index']);

// Страницы модпаков
Router::get('/modrinth', [ModpackController::class, 'showModrinth']);
Router::get('/curseforge', [ModpackController::class, 'showCurseforge']);
Router::get('/modpack/:platform/:slug', [ModpackController::class, 'show']);

// Аутентификация
Router::get('/login', [AuthController::class, 'showLogin'])->middleware('guest');
Router::get('/register', [AuthController::class, 'showRegister'])->middleware('guest');
Router::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->middleware('guest');
Router::get('/logout', [AuthController::class, 'logout']);

// Профиль
Router::get('/@:username', [ProfileController::class, 'show']);

// Настройки
Router::get('/settings', [SettingsController::class, 'index'])->middleware('auth');

// Чат
Router::get('/chat/:chatId', [ChatController::class, 'show'])->middleware('auth');

// Админ-панель
Router::get('/admin', [AdminController::class, 'index'])->middleware(['auth', 'admin']);
