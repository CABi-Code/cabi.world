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

Router::prefix('/api')->group(function() {
    // Auth routes
    Router::post('/auth/login', [AuthController::class, 'login'])
        ->middleware(['csrf', 'rate_limit:5,60']);
    
    Router::post('/auth/register', [AuthController::class, 'register'])
        ->middleware(['csrf', 'rate_limit:3,60']);
    
    Router::post('/auth/refresh', [AuthController::class, 'refresh'])
        ->middleware('csrf');
    
    // User routes
    Router::group(['middleware' => 'auth'], function() {
        Router::put('/user/update', [UserController::class, 'update'])->middleware('csrf');
        Router::post('/user/avatar', [UserController::class, 'uploadAvatar'])->middleware('csrf');
        Router::delete('/user/avatar', [UserController::class, 'deleteAvatar'])->middleware('csrf');
        Router::post('/user/banner', [UserController::class, 'uploadBanner'])->middleware('csrf');
        Router::delete('/user/banner', [UserController::class, 'deleteBanner'])->middleware('csrf');
        
        // Application routes
        Router::post('/modpack/apply', [ModpackController::class, 'apply'])->middleware('csrf');
        Router::post('/application', [ApplicationController::class, 'create'])->middleware('csrf');
        Router::put('/application/:id', [ApplicationController::class, 'update'])->middleware('csrf');
        Router::delete('/application/:id', [ApplicationController::class, 'delete'])->middleware('csrf');
        Router::post('/application/:id/toggle-hidden', [ApplicationController::class, 'toggleHidden'])->middleware('csrf');
        Router::delete('/application/image/:imageId', [ApplicationController::class, 'deleteImage'])->middleware('csrf');
        
        // Notification routes
        Router::get('/notifications', [NotificationController::class, 'index']);
        Router::post('/notifications/read', [NotificationController::class, 'read'])->middleware('csrf');
        
        // Chat routes
        Router::get('/chat/messages', [ChatController::class, 'getMessages']);
        Router::get('/chat/messages/new', [ChatController::class, 'getNewMessages']);
        Router::post('/chat/send', [ChatController::class, 'send'])->middleware('csrf');
        Router::post('/chat/like', [ChatController::class, 'like'])->middleware('csrf');
        Router::post('/chat/poll/vote', [ChatController::class, 'votePoll'])->middleware('csrf');
        
        // Community routes (will be handled by ChatController or separate CommunityController)
    });
    
    // Admin routes
    Router::group(['prefix' => '/admin', 'middleware' => ['auth', 'admin']], function() {
        Router::post('/application/status', [AdminController::class, 'setApplicationStatus'])->middleware('csrf');
        Router::post('/application/delete', [AdminController::class, 'deleteApplication'])->middleware('csrf');
    });
});
