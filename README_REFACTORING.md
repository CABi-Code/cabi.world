# Инструкция по использованию новой структуры

## 🚀 Быстрый старт

Проект был полностью переписан в новую структуру. Все маршруты теперь обрабатываются через новый роутер.

## 📁 Структура проекта

```
cabi.world/
├── app/
│   ├── Controllers/          # Контроллеры
│   │   ├── Api/              # API контроллеры
│   │   └── Web/              # Веб контроллеры
│   ├── Http/                 # HTTP слой
│   │   ├── Middleware/       # Middleware
│   │   ├── Request.php       # HTTP Request
│   │   ├── Response.php      # HTTP Response
│   │   ├── Router.php        # Роутер
│   │   └── Route.php         # Класс маршрута
│   ├── Services/             # Бизнес-логика
│   │   ├── AuthService.php
│   │   └── RateLimitService.php
│   ├── Validators/           # Валидаторы
│   └── ...
├── routes/                   # Маршруты
│   ├── web.php              # Веб-маршруты
│   └── api.php              # API маршруты
└── public/
    └── index.php            # Точка входа
```

## 📝 Добавление нового маршрута

### Веб-маршрут

Откройте `routes/web.php`:

```php
use App\Http\Router;
use App\Controllers\Web\YourController;

Router::get('/your-route', [YourController::class, 'method'])
    ->middleware('auth'); // опционально
```

### API маршрут

Откройте `routes/api.php`:

```php
use App\Http\Router;
use App\Controllers\Api\YourController;

Router::prefix('/api')->group(function() {
    Router::post('/your-endpoint', [YourController::class, 'method'])
        ->middleware(['auth', 'csrf']);
});
```

## 🛡️ Middleware

Доступные middleware:

- `auth` - требует авторизации
- `guest` - требует, чтобы пользователь был гостем
- `admin` - требует прав администратора
- `csrf` - проверяет CSRF токен
- `rate_limit:5,60` - ограничивает запросы (5 запросов в 60 секунд)

Пример использования:

```php
Router::post('/api/endpoint', [Controller::class, 'method'])
    ->middleware(['auth', 'csrf', 'rate_limit:10,60']);
```

## 🎮 Создание контроллера

### API контроллер

```php
<?php

namespace App\Controllers\Api;

use App\Http\Request;
use App\Http\Response;

class YourController
{
    public function method(Request $request): void
    {
        $data = $request->all();
        $user = $request->user();
        
        // Валидация
        // Бизнес-логика
        
        Response::json(['success' => true]);
    }
}
```

### Web контроллер

```php
<?php

namespace App\Controllers\Web;

use App\Http\Request;

class YourController
{
    public function method(Request $request): void
    {
        $title = 'Заголовок страницы';
        ob_start();
        require TEMPLATES_PATH . '/pages/your-page.php';
        $content = ob_get_clean();
        require TEMPLATES_PATH . '/layouts/main.php';
    }
}
```

## 📦 Использование Request

```php
// Получить все данные
$data = $request->all();

// Получить конкретное значение
$value = $request->get('key', 'default');

// Проверить наличие ключа
if ($request->has('key')) {
    // ...
}

// Работа с файлами
if ($request->hasFile('file')) {
    $file = $request->file('file');
    $files = $request->files('files'); // массив файлов
}

// Получить пользователя
$user = $request->user();

// Метод запроса
if ($request->isMethod('POST')) {
    // ...
}
```

## 📤 Использование Response

```php
// JSON ответ
Response::json(['success' => true]);

// JSON с ошибкой
Response::error('Error message', 400);

// JSON с несколькими ошибками
Response::errors(['field' => 'Error'], 400);

// Редирект
Response::redirect('/path');

// Успешный ответ
Response::success(['data' => $data]);
```

## ✅ Валидация

Создайте валидатор в `app/Validators/`:

```php
<?php

namespace App\Validators;

class YourValidator
{
    public function validateCreate(array $data): array
    {
        $errors = [];
        
        if (empty($data['field'])) {
            $errors['field'] = 'Field is required';
        }
        
        return $errors;
    }
}
```

Использование в контроллере:

```php
$validator = new YourValidator();
$errors = $validator->validateCreate($request->all());

if (!empty($errors)) {
    Response::errors($errors, 400);
    return;
}
```

## 🔄 Миграция со старого кода

### Старый способ:
```php
if (!$user) json(['error' => 'Unauthorized'], 401);
$data = $input['field'] ?? '';
```

### Новый способ:
```php
$user = $request->user();
if (!$user) {
    Response::error('Unauthorized', 401);
    return;
}
$data = $request->get('field', '');
```

## ⚠️ Важные замечания

1. **Старые файлы маршрутов** (`index/Routes/` и `index/RoutesAPI/`) не удалены для обратной совместимости. Их можно удалить после проверки.

2. **Глобальные переменные** `$user` и `$unreadNotifications` все еще доступны для совместимости со старыми шаблонами.

3. **Функции из bootstrap.php** (`json()`, `redirect()`) все еще работают, но рекомендуется использовать `Response::json()` и `Response::redirect()`.

## 🐛 Отладка

Если маршрут не работает:

1. Проверьте, что маршрут добавлен в `routes/web.php` или `routes/api.php`
2. Проверьте, что контроллер существует и метод публичный
3. Проверьте middleware - возможно, он блокирует запрос
4. Проверьте логи в `storage/logs/`

## 📚 Дополнительная документация

- `ANALYSIS.md` - Анализ проблем старого кода
- `NEW_STRUCTURE_PROPOSAL.md` - Предложение новой структуры
- `MIGRATION_COMPLETE.md` - Список выполненных задач
