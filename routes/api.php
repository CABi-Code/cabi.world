<?php

declare(strict_types=1);

use App\Http\Router;
use App\Controllers\Api\AuthController;
use App\Controllers\Api\UserController;
use App\Controllers\Api\ApplicationController;
use App\Controllers\Api\ModpackController;
use App\Controllers\Api\ChatController;
use App\Controllers\Api\NotificationController;
use App\Controllers\Api\AdminController;
use App\Controllers\Api\CommunityController;
use App\Controllers\Api\CaptchaController;
use App\Controllers\Api\UserFolderController;
use App\Controllers\Api\ModpackSelectorController;

Router::post('/api/captcha/solve', [CaptchaController::class, 'solve'])
    ->middleware('csrf');

Router::prefix('/api')->group(function() {
    // Auth routes
    Router::post('/auth/login', [AuthController::class, 'login'])
        ->middleware(['csrf', 'rate_limit:5,60']);
    
    Router::post('/auth/register', [AuthController::class, 'register'])
        ->middleware(['csrf', 'rate_limit:3,60']);
    
    Router::post('/auth/refresh', [AuthController::class, 'refresh'])
        ->middleware('csrf');
    
	// Modpack Selector
	Router::get('/modpack-selector/modal', [ModpackSelectorController::class, 'modal']);
	Router::get('/modpack-selector/list', [ModpackSelectorController::class, 'list']);
	Router::get('/modpack-selector/search', [ModpackSelectorController::class, 'search']);
	
    // User routes (требуют авторизации)
    Router::group(['middleware' => 'auth'], function() {
        // User profile
        Router::put('/user/update', [UserController::class, 'update'])->middleware('csrf');
        Router::post('/user/update', [UserController::class, 'update'])->middleware('csrf');
        Router::post('/user/avatar', [UserController::class, 'uploadAvatar'])->middleware('csrf');
        Router::delete('/user/avatar', [UserController::class, 'deleteAvatar'])->middleware('csrf');
        Router::delete('/user/avatar/delete', [UserController::class, 'deleteAvatar'])->middleware('csrf');
        Router::post('/user/banner', [UserController::class, 'uploadBanner'])->middleware('csrf');
        Router::delete('/user/banner', [UserController::class, 'deleteBanner'])->middleware('csrf');
        Router::delete('/user/banner/delete', [UserController::class, 'deleteBanner'])->middleware('csrf');
        Router::post('/user/password', [UserController::class, 'changePassword'])->middleware('csrf');
        
        // Applications
        Router::post('/modpack/apply', [ApplicationController::class, 'create'])->middleware('csrf');
        Router::post('/application/update', [ApplicationController::class, 'update'])->middleware('csrf');
        Router::post('/application/delete', [ApplicationController::class, 'delete'])->middleware('csrf');
        Router::post('/application/:id/toggle-hidden', [ApplicationController::class, 'toggleHidden'])->middleware('csrf');
        Router::post('/application/image/:id/delete', [ApplicationController::class, 'deleteImage'])->middleware('csrf');
        
        // Notifications
        Router::get('/notifications', [NotificationController::class, 'getAll']);
        Router::post('/notifications/read', [NotificationController::class, 'markAsRead'])->middleware('csrf');
        Router::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->middleware('csrf');
        
        // Chat
        Router::get('/chat/messages', [ChatController::class, 'getMessages']);
        Router::post('/chat/send', [ChatController::class, 'sendMessage'])->middleware('csrf');
        Router::post('/chat/delete', [ChatController::class, 'deleteMessage'])->middleware('csrf');
        Router::post('/chat/like', [ChatController::class, 'toggleLike'])->middleware('csrf');
        Router::post('/chat/poll/create', [ChatController::class, 'createPoll'])->middleware('csrf');
        Router::post('/chat/poll/vote', [ChatController::class, 'votePoll'])->middleware('csrf');
        Router::post('/chat/settings', [ChatController::class, 'updateSettings'])->middleware('csrf');
        
        // User Folder (Моя папка)
        Router::get('/user-folder/structure', [UserFolderController::class, 'getStructure']);
        Router::get('/user-folder/item', [UserFolderController::class, 'getItem']);
        Router::post('/user-folder/create', [UserFolderController::class, 'create'])->middleware('csrf');
        Router::post('/user-folder/update', [UserFolderController::class, 'update'])->middleware('csrf');
        Router::post('/user-folder/delete', [UserFolderController::class, 'delete'])->middleware('csrf');
        Router::post('/user-folder/move', [UserFolderController::class, 'move'])->middleware('csrf');
        Router::post('/user-folder/toggle-collapse', [UserFolderController::class, 'toggleCollapse'])->middleware('csrf');
        Router::post('/user-folder/subscribe', [UserFolderController::class, 'subscribe'])->middleware('csrf');
        Router::post('/user-folder/unsubscribe', [UserFolderController::class, 'unsubscribe'])->middleware('csrf');
        Router::post('/user-folder/show-application', [UserFolderController::class, 'showApplication'])->middleware('csrf');
        Router::post('/user-folder/hide-application', [UserFolderController::class, 'hideApplication'])->middleware('csrf');
       
    });
    
    // Admin routes (требуют авторизации + права админа)
    Router::group(['prefix' => '/admin', 'middleware' => ['auth', 'admin']], function() {
        Router::get('/application/:id', [AdminController::class, 'getApplication']);
        Router::post('/application/:id/modal', [AdminController::class, 'getApplicationModal']);
        Router::post('/application/status', [AdminController::class, 'setApplicationStatus'])->middleware('csrf');
        Router::post('/application/delete', [AdminController::class, 'deleteApplication'])->middleware('csrf');
    });
});
