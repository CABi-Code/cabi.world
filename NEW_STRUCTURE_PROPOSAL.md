# Предложение новой структуры проекта

## 🎯 Цели рефакторинга

1. Упростить навигацию по коду
2. Устранить дублирование
3. Улучшить безопасность
4. Повысить производительность
5. Следовать стандартам PHP (PSR)

## 📁 Новая структура проекта

```
cabi.world/
├── app/
│   ├── Controllers/              # Контроллеры (новая папка)
│   │   ├── Web/                  # Веб-контроллеры
│   │   │   ├── HomeController.php
│   │   │   ├── AuthController.php
│   │   │   ├── ProfileController.php
│   │   │   ├── ModpackController.php
│   │   │   ├── ChatController.php
│   │   │   ├── SettingsController.php
│   │   │   └── AdminController.php
│   │   └── Api/                  # API контроллеры
│   │       ├── AuthController.php
│   │       ├── UserController.php
│   │       ├── ApplicationController.php
│   │       ├── ModpackController.php
│   │       ├── ChatController.php
│   │       ├── NotificationController.php
│   │       └── AdminController.php
│   │
│   ├── Http/                     # HTTP слой (новая папка)
│   │   ├── Request.php           # Обертка над $_REQUEST
│   │   ├── Response.php          # Унифицированные ответы
│   │   ├── Router.php            # Роутер
│   │   └── Middleware/           # Middleware
│   │       ├── AuthMiddleware.php
│   │       ├── AdminMiddleware.php
│   │       ├── CsrfMiddleware.php
│   │       ├── RateLimitMiddleware.php
│   │       └── ValidateRequestMiddleware.php
│   │
│   ├── Services/                 # Бизнес-логика (расширение)
│   │   ├── AuthService.php       # Объединить AuthManager
│   │   ├── ImageService.php
│   │   ├── ValidationService.php # Новая - валидация
│   │   ├── RateLimitService.php  # Новая - rate limiting
│   │   └── CacheService.php      # Новая - кэширование
│   │
│   ├── Repository/                # Без изменений (убрать трейты)
│   │   ├── UserRepository.php    # Объединить трейты
│   │   ├── ApplicationRepository.php
│   │   ├── ChatMessageRepository.php # Объединить трейты
│   │   ├── CommunityRepository.php  # Объединить трейты
│   │   ├── ModpackRepository.php
│   │   ├── NotificationRepository.php
│   │   └── RefreshTokenRepository.php
│   │
│   ├── Core/                     # Без изменений
│   │   ├── Database.php
│   │   ├── DbFields.php
│   │   ├── Role.php
│   │   └── Security.php
│   │
│   ├── Auth/                     # Упростить (убрать трейты)
│   │   ├── AuthService.php       # Объединить AuthManager + трейты
│   │   └── JWT.php
│   │
│   ├── Validators/               # Новая папка
│   │   ├── UserValidator.php
│   │   ├── ApplicationValidator.php
│   │   ├── MessageValidator.php
│   │   └── ImageValidator.php
│   │
│   └── bootstrap.php
│
├── routes/                       # Новая папка (вместо index/)
│   ├── web.php                   # Веб-маршруты
│   ├── api.php                   # API маршруты
│   └── admin.php                 # Админ маршруты
│
├── config/                       # Без изменений
│   ├── app.php
│   └── database.php
│
├── public/                       # Без изменений
│   ├── index.php                 # Точка входа
│   ├── css/
│   ├── js/
│   └── uploads/
│
├── templates/                    # Без изменений
│   ├── layouts/
│   ├── pages/
│   └── components/
│
├── storage/                      # Новая папка
│   ├── cache/                    # Кэш
│   ├── logs/                     # Логи
│   └── sessions/                 # Сессии (если файловые)
│
└── tests/                        # Новая папка (для будущего)
    ├── Unit/
    └── Feature/
```

## 🔄 Изменения по компонентам

### 1. Роутинг

#### Было:
```php
// public/index.php
include_once '../index/RoutesAPI/RoutesAPI.php';
include_once '../index/Routes/.PageRoutes.php';
// ...
```

#### Станет:
```php
// public/index.php
require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Http\Router;

$router = new Router();
require_once ROOT_PATH . '/routes/web.php';
require_once ROOT_PATH . '/routes/api.php';
require_once ROOT_PATH . '/routes/admin.php';

$router->dispatch();
```

#### routes/web.php:
```php
<?php
use App\Http\Router;
use App\Controllers\Web\HomeController;
use App\Controllers\Web\AuthController;
use App\Controllers\Web\ProfileController;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\CsrfMiddleware;

Router::get('/', [HomeController::class, 'index']);

Router::get('/login', [AuthController::class, 'showLogin'])->middleware('guest');
Router::post('/login', [AuthController::class, 'login'])->middleware(['guest', 'csrf']);
Router::get('/register', [AuthController::class, 'showRegister'])->middleware('guest');
Router::post('/register', [AuthController::class, 'register'])->middleware(['guest', 'csrf']);

Router::get('/@:username', [ProfileController::class, 'show']);

Router::get('/settings', [SettingsController::class, 'index'])->middleware('auth');
// ...
```

#### routes/api.php:
```php
<?php
use App\Http\Router;
use App\Controllers\Api\AuthController;
use App\Controllers\Api\UserController;
use App\Http\Middleware\CsrfMiddleware;
use App\Http\Middleware\RateLimitMiddleware;

Router::prefix('/api')->group(function() {
    Router::post('/auth/login', [AuthController::class, 'login'])
        ->middleware(['csrf', 'rate_limit:5,60']); // 5 запросов в минуту
    
    Router::post('/auth/register', [AuthController::class, 'register'])
        ->middleware(['csrf', 'rate_limit:3,60']);
    
    Router::post('/auth/refresh', [AuthController::class, 'refresh'])
        ->middleware('csrf');
    
    Router::prefix('/user')->middleware('auth')->group(function() {
        Router::put('/update', [UserController::class, 'update']);
        Router::post('/avatar', [UserController::class, 'uploadAvatar']);
        Router::delete('/avatar', [UserController::class, 'deleteAvatar']);
        Router::post('/banner', [UserController::class, 'uploadBanner']);
        Router::delete('/banner', [UserController::class, 'deleteBanner']);
    });
    
    Router::prefix('/application')->middleware('auth')->group(function() {
        Router::post('/', [ApplicationController::class, 'create']);
        Router::put('/:id', [ApplicationController::class, 'update']);
        Router::delete('/:id', [ApplicationController::class, 'delete']);
        Router::post('/:id/toggle-hidden', [ApplicationController::class, 'toggleHidden']);
    });
});
```

### 2. Контроллеры

#### Пример: app/Controllers/Api/ApplicationController.php
```php
<?php

namespace App\Controllers\Api;

use App\Http\Request;
use App\Http\Response;
use App\Repository\ApplicationRepository;
use App\Services\ImageService;
use App\Validators\ApplicationValidator;

class ApplicationController
{
    private ApplicationRepository $appRepo;
    private ImageService $imageService;
    private ApplicationValidator $validator;

    public function __construct()
    {
        $this->appRepo = new ApplicationRepository();
        $this->imageService = new ImageService();
        $this->validator = new ApplicationValidator();
    }

    public function create(Request $request): Response
    {
        $data = $request->all();
        
        // Валидация
        $errors = $this->validator->validateCreate($data);
        if (!empty($errors)) {
            return Response::json(['errors' => $errors], 400);
        }
        
        // Бизнес-логика
        $appId = $this->appRepo->create(
            $data['modpack_id'],
            $request->user()['id'],
            $data['message'],
            $data['discord'] ?? null,
            $data['telegram'] ?? null,
            $data['vk'] ?? null,
            $data['relevant_until'] ?? null
        );
        
        // Обработка изображений
        if ($request->hasFile('images')) {
            foreach ($request->files('images') as $image) {
                $path = $this->imageService->uploadApplicationImage($image, $appId);
                if ($path) {
                    $this->appRepo->addImage($appId, $path);
                }
            }
        }
        
        return Response::json(['success' => true, 'id' => $appId]);
    }

    public function update(Request $request, int $id): Response
    {
        $app = $this->appRepo->findById($id);
        if (!$app || $app['user_id'] !== $request->user()['id']) {
            return Response::json(['error' => 'Not found'], 404);
        }
        
        $data = $request->all();
        $errors = $this->validator->validateUpdate($data);
        if (!empty($errors)) {
            return Response::json(['errors' => $errors], 400);
        }
        
        $this->appRepo->update(
            $id,
            $request->user()['id'],
            $data['message'],
            $data['discord'] ?? null,
            $data['telegram'] ?? null,
            $data['vk'] ?? null,
            $data['relevant_until'] ?? null
        );
        
        return Response::json(['success' => true]);
    }

    public function delete(Request $request, int $id): Response
    {
        $app = $this->appRepo->findById($id);
        if (!$app || $app['user_id'] !== $request->user()['id']) {
            return Response::json(['error' => 'Not found'], 404);
        }
        
        $this->appRepo->delete($id, $request->user()['id']);
        return Response::json(['success' => true]);
    }
}
```

### 3. Упрощение AuthManager

#### Было: AuthManager + 9 трейтов
#### Станет: Один класс AuthService

```php
<?php

namespace App\Services;

use App\Repository\UserRepository;
use App\Repository\RefreshTokenRepository;
use App\Auth\JWT;

class AuthService
{
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

    public function register(string $login, string $email, string $password, string $username): array
    {
        // Вся логика регистрации здесь
        // (объединить RegisterTrait)
    }

    public function login(string $login, string $password, string $ip, string $userAgent): array
    {
        // Вся логика входа здесь
        // (объединить LoginTrait)
    }

    public function refresh(string $refreshToken): array
    {
        // Вся логика обновления токена здесь
        // (объединить RefreshTrait)
    }

    public function logout(string $refreshToken): void
    {
        // Вся логика выхода здесь
        // (объединить LogoutTrait)
    }

    public function getCurrentUser(): ?array
    {
        // Вся логика получения текущего пользователя здесь
        // (объединить GetCurrentUserTrait)
    }

    // Остальные методы...
}
```

### 4. Валидация

#### app/Validators/ApplicationValidator.php
```php
<?php

namespace App\Validators;

use App\Repository\ApplicationRepository;

class ApplicationValidator
{
    public function validateCreate(array $data): array
    {
        $errors = [];
        
        // Modpack ID
        if (empty($data['modpack_id']) || !is_numeric($data['modpack_id'])) {
            $errors['modpack_id'] = 'Invalid modpack ID';
        }
        
        // Message
        $message = trim($data['message'] ?? '');
        if (empty($message)) {
            $errors['message'] = 'Message is required';
        } elseif (mb_strlen($message) > ApplicationRepository::MAX_MESSAGE_LENGTH) {
            $errors['message'] = 'Message too long';
        }
        
        // Relevant until
        if (isset($data['relevant_until'])) {
            $validation = $this->validateRelevantUntil($data['relevant_until']);
            if (!$validation['valid']) {
                $errors['relevant_until'] = $validation['error'];
            }
        }
        
        return $errors;
    }

    public function validateUpdate(array $data): array
    {
        // Аналогично validateCreate
    }

    private function validateRelevantUntil(?string $date): array
    {
        // Логика валидации даты
    }
}
```

### 5. Request/Response классы

#### app/Http/Request.php
```php
<?php

namespace App\Http;

class Request
{
    private array $data;
    private array $files;
    private ?array $user = null;

    public function __construct()
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $this->data = json_decode(file_get_contents('php://input'), true) ?? [];
        } elseif (strpos($contentType, 'multipart/form-data') !== false) {
            $this->data = $_POST;
        } else {
            $this->data = $_REQUEST;
        }
        
        $this->files = $_FILES ?? [];
    }

    public function all(): array
    {
        return $this->data;
    }

    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) && $this->files[$key]['error'] === UPLOAD_ERR_OK;
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function files(string $key): array
    {
        if (!isset($this->files[$key])) {
            return [];
        }
        
        $files = $this->files[$key];
        if (isset($files['name']) && !is_array($files['name'])) {
            return [$files];
        }
        
        $result = [];
        foreach ($files['name'] as $i => $name) {
            $result[] = [
                'name' => $name,
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i],
            ];
        }
        return $result;
    }

    public function user(): ?array
    {
        return $this->user;
    }

    public function setUser(array $user): void
    {
        $this->user = $user;
    }
}
```

#### app/Http/Response.php
```php
<?php

namespace App\Http;

class Response
{
    public static function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        
        // Добавляем ошибки БД если есть
        if (class_exists('App\Core\Database')) {
            $dbErrors = \App\Core\Database::getErrors();
            if (!empty($dbErrors)) {
                $data['_db_errors'] = $dbErrors;
            }
        }
        
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function redirect(string $url): void
    {
        header("Location: $url");
        exit;
    }

    public static function view(string $template, array $data = []): void
    {
        extract($data);
        require TEMPLATES_PATH . '/' . $template . '.php';
    }
}
```

### 6. Middleware

#### app/Http/Middleware/RateLimitMiddleware.php
```php
<?php

namespace App\Http\Middleware;

use App\Http\Request;
use App\Services\RateLimitService;

class RateLimitMiddleware
{
    private RateLimitService $rateLimitService;

    public function __construct()
    {
        $this->rateLimitService = new RateLimitService();
    }

    public function handle(Request $request, callable $next, int $maxAttempts, int $windowSeconds): void
    {
        $key = $this->getKey($request);
        
        if (!$this->rateLimitService->check($key, $maxAttempts, $windowSeconds)) {
            Response::json(['error' => 'Too many requests'], 429);
        }
        
        $next($request);
    }

    private function getKey(Request $request): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $route = $_SERVER['REQUEST_URI'] ?? '';
        return "rate_limit:{$ip}:{$route}";
    }
}
```

## 📋 План миграции

### Этап 1: Подготовка (1-2 дня)
1. Создать новую структуру папок
2. Создать базовые классы (Router, Request, Response)
3. Настроить автозагрузку

### Этап 2: Роутинг (2-3 дня)
1. Реализовать Router
2. Перенести маршруты в routes/
3. Обновить public/index.php

### Этап 3: Контроллеры (3-4 дня)
1. Создать контроллеры для API
2. Перенести логику из файлов маршрутов в контроллеры
3. Протестировать каждый эндпоинт

### Этап 4: Упрощение (2-3 дня)
1. Объединить трейты в классы
2. Упростить AuthManager → AuthService
3. Упростить репозитории

### Этап 5: Валидация и безопасность (2-3 дня)
1. Создать валидаторы
2. Добавить rate limiting
3. Улучшить CSRF защиту
4. Добавить валидацию файлов

### Этап 6: Оптимизация (2-3 дня)
1. Оптимизировать SQL запросы
2. Добавить кэширование
3. Исправить N+1 проблемы

### Этап 7: Тестирование (2-3 дня)
1. Протестировать все эндпоинты
2. Проверить безопасность
3. Провести нагрузочное тестирование

**Общее время: 14-21 день**

## 🎯 Преимущества новой структуры

1. ✅ **Читаемость**: Понятная структура, следует стандартам
2. ✅ **Масштабируемость**: Легко добавлять новые функции
3. ✅ **Тестируемость**: Контроллеры легко тестировать
4. ✅ **Безопасность**: Централизованная валидация и защита
5. ✅ **Производительность**: Оптимизированные запросы и кэширование
6. ✅ **Поддерживаемость**: Меньше дублирования, больше переиспользования

## ⚠️ Риски и рекомендации

1. **Постепенная миграция**: Не переписывать всё сразу
2. **Резервные копии**: Делать бэкапы перед изменениями
3. **Тестирование**: Тестировать каждый этап
4. **Документация**: Обновлять документацию по мере изменений
