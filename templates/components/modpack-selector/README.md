# Модпак-селектор - Инструкция по интеграции

## Структура файлов

```
templates/components/modpack-selector/
├── modpack-selector.php    # Основной компонент модалки
├── search-bar.php          # Панель поиска и сортировки
├── modpack-list.php        # Контейнер для списка
├── demo-button.php         # Демо-кнопка для тестирования
└── demo-button-js.php      # JS для демо-кнопки

public/css/components/
└── modpack-selector.css    # Стили компонента

public/js/modpack-selector/
├── index.js                # Главный модуль
├── api.js                  # API для загрузки данных
└── render.js               # Рендеринг карточек

app/Controllers/Api/
└── ModpackSelectorController.php   # API контроллер
```

## 1. Добавить маршруты в routes/api.php

```php
use App\Controllers\Api\ModpackSelectorController;

// Внутри Router::prefix('/api')->group():
Router::get('/modpack-selector/modal', [ModpackSelectorController::class, 'modal']);
Router::get('/modpack-selector/list', [ModpackSelectorController::class, 'list']);
Router::get('/modpack-selector/search', [ModpackSelectorController::class, 'search']);
```

## 2. Подключить CSS в layout

В `templates/layouts/main.php` добавить:

```html
<link rel="stylesheet" href="/css/components/modpack-selector.css">
```

## 3. Использование в коде

### Вариант 1: Через демо-кнопку (для тестирования)

```php
<?php include_once TEMPLATES_PATH . '/components/modpack-selector/demo-button.php'; ?>
```

### Вариант 2: Программно через JS

```html
<button type="button" id="mySelectButton">Выбрать модпак</button>

<script type="module">
import { openModpackSelector } from '/js/modpack-selector/index.js';

document.getElementById('mySelectButton').addEventListener('click', () => {
    openModpackSelector((selectedModpack) => {
        // selectedModpack содержит:
        // - id: string (external_id)
        // - platform: 'modrinth' | 'curseforge'
        // - slug: string
        // - name: string
        // - icon_url: string | null
        // - downloads: number
        // - app_count: number
        
        console.log('Выбран модпак:', selectedModpack);
    });
});
</script>
```

### Вариант 3: Без модулей (глобальная функция)

Добавить в конец `public/js/app.js`:

```js
import { openModpackSelector } from './modpack-selector/index.js';
window.openModpackSelector = openModpackSelector;
```

Затем использовать:

```html
<button onclick="openModpackSelector(handleModpackSelect)">Выбрать</button>
<script>
function handleModpackSelect(modpack) {
    console.log('Выбран:', modpack);
}
</script>
```

## Особенности

1. **Подгрузка модалки** - модалка загружается через AJAX при первом открытии
2. **Кэширование** - после первой загрузки модпаки кэшируются
3. **Объединение источников** - модпаки из БД + Modrinth + CurseForge
4. **Сортировка** - по скачиваниям или по количеству заявок
5. **Поиск** - фильтрация по названию в реальном времени
6. **Заглушка** - если нет иконки, показывается иконка модпака

## API ответы

### GET /api/modpack-selector/list

```json
{
    "success": true,
    "modpacks": [
        {
            "id": "abc123",
            "platform": "modrinth",
            "slug": "cobblemon",
            "name": "Cobblemon",
            "icon_url": "https://...",
            "downloads": 1500000,
            "app_count": 12
        }
    ]
}
```

### GET /api/modpack-selector/modal

Возвращает HTML модального окна.
