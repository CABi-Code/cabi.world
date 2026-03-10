# cabi.world

Веб-платформа для Minecraft-сообщества: каталог модпаков (Modrinth / CurseForge), система заявок на совместную игру, профили пользователей, чаты, пользовательские папки и мониторинг серверов.

## Стек технологий

- **Backend:** PHP 8.1+ (собственный MVC-фреймворк, без Laravel/Symfony)
- **База данных:** MariaDB / MySQL (utf8mb4, PDO)
- **Frontend:** Vanilla JS + CSS (без React/Vue), PHP-шаблоны
- **Аутентификация:** JWT (access + refresh tokens) + сессии
- **Капча:** Cloudflare Turnstile
- **Обработка изображений:** Intervention Image 2.x
- **Внешние API:** CurseForge API, Modrinth API

## Структура проекта

```
cabi.world/
├── app/                        # Ядро приложения (backend)
│   ├── bootstrap.php           # Точка инициализации: env, сессии, хелперы (render, view, json, redirect, csrf_token и др.)
│   ├── Auth/
│   │   └── JWT.php             # Генерация и валидация JWT-токенов
│   ├── Controllers/
│   │   ├── BaseController.php  # Базовый контроллер (общая логика)
│   │   ├── Api/                # API-контроллеры (возвращают JSON)
│   │   │   ├── AdminController.php         # Админ: управление заявками
│   │   │   ├── ApplicationController.php   # CRUD заявок на модпаки
│   │   │   ├── AuthController.php          # Логин, регистрация, refresh токенов
│   │   │   ├── CaptchaController.php       # Решение капчи (Turnstile)
│   │   │   ├── ChatController.php          # Сообщения, лайки, опросы, настройки чата
│   │   │   │   ├── ActionsTrait.php        # Лайки, удаление, опросы
│   │   │   │   └── MessagesTrait.php       # Получение и отправка сообщений
│   │   │   ├── ModpackController.php       # Данные модпаков (API-прокси к Modrinth/CurseForge)
│   │   │   ├── ModpackSelectorController.php # Модальное окно выбора модпака
│   │   │   ├── NotificationController.php  # Уведомления пользователя
│   │   │   ├── ServerPingController.php    # Пинг Minecraft-серверов
│   │   │   │   ├── MotdTrait.php           # Парсинг MOTD сервера
│   │   │   │   ├── PingTrait.php           # Логика пинга
│   │   │   │   └── SkinsTrait.php          # Скины игроков
│   │   │   ├── UserController.php          # Профиль, аватар, баннер, пароль
│   │   │   └── UserFolderController.php    # Пользовательские папки (CRUD, подписки, перемещение)
│   │   └── Web/                # Веб-контроллеры (возвращают HTML)
│   │       ├── AdminController.php     # Страница админ-панели
│   │       ├── AuthController.php      # Страницы логина/регистрации
│   │       ├── ChatController.php      # Страница чата
│   │       ├── HomeController.php      # Главная страница (лента заявок)
│   │       ├── ItemController.php      # Страница элемента папки (/item/:id)
│   │       ├── ModpackController.php   # Страницы каталога и карточки модпака
│   │       ├── ProfileController.php   # Профиль пользователя (/@username)
│   │       └── SettingsController.php  # Страница настроек
│   ├── Core/
│   │   ├── Database.php        # Singleton PDO-обёртка, query builder
│   │   ├── DbFields.php        # Маппинг полей БД
│   │   ├── Role.php            # Роли пользователей (user, premium, moderator, admin)
│   │   ├── Security.php        # Утилиты безопасности
│   │   │   └── routes.json     # Конфигурация безопасности маршрутов
│   │   └── Template.php        # Движок шаблонов
│   ├── Http/
│   │   ├── Request.php         # Обёртка над HTTP-запросом (данные, файлы, метод, URI)
│   │   ├── Response.php        # HTTP-ответ
│   │   ├── Router.php          # Основной роутер (статический)
│   │   │   ├── DispatchTrait.php           # Диспетчеризация запросов
│   │   │   ├── GroupTrait.php              # Группировка маршрутов с префиксами
│   │   │   ├── MiddlewareResolverTrait.php # Резолвинг middleware по имени
│   │   │   └── RouteMethodsTrait.php       # Методы GET/POST/PUT/DELETE
│   │   ├── Route.php           # Объект маршрута
│   │   │   ├── HandlerTrait.php    # Обработчик маршрута
│   │   │   ├── MatchingTrait.php   # Сопоставление URL с паттерном
│   │   │   ├── MiddlewareTrait.php # Привязка middleware к маршруту
│   │   │   └── ParamsTrait.php     # Параметры маршрута (:id, :slug)
│   │   ├── RouterGroup.php     # Группа маршрутов
│   │   └── Middleware/
│   │       ├── MiddlewareInterface.php     # Интерфейс middleware
│   │       ├── AdminMiddleware.php         # Проверка роли admin
│   │       ├── AuthMiddleware.php          # Проверка JWT/сессии
│   │       ├── CsrfMiddleware.php          # CSRF-защита
│   │       ├── GuestMiddleware.php         # Только для неавторизованных
│   │       ├── RateLimitMiddleware.php     # Ограничение частоты запросов
│   │       └── RateLimitBlockMiddleware.php # Блокировка при превышении лимита
│   ├── Repository/             # Слой работы с БД (Data Access)
│   │   ├── ApplicationRepository.php       # Заявки на модпаки
│   │   │   ├── ByModpackTrait.php          # Выборка по модпаку
│   │   │   ├── ForAdminTrait.php           # Выборка для админки
│   │   │   ├── ImageTrait.php              # Изображения заявок
│   │   │   ├── PendingTrait.php            # Ожидающие заявки
│   │   │   ├── ValidateRelevantUntilTrait.php # Проверка актуальности
│   │   │   └── WarpTrait.php               # Warp-функционал
│   │   ├── ChatMessageRepository.php       # Сообщения чата
│   │   │   ├── AuxiliaryTrait.php          # Вспомогательные методы
│   │   │   ├── ImagesTrait.php             # Изображения в сообщениях
│   │   │   ├── MessagesTrait.php           # CRUD сообщений
│   │   │   └── PollsTrait.php              # Опросы в чате
│   │   ├── ModpackRepository.php           # Модпаки (кэш + внешние API)
│   │   ├── NotificationRepository.php      # Уведомления
│   │   ├── RefreshTokenRepository.php      # Refresh-токены
│   │   ├── ServerPingRepository.php        # Мониторинг серверов
│   │   │   ├── HistoryTrait.php            # История пингов
│   │   │   └── StatusTrait.php             # Текущий статус
│   │   ├── StatsRepository.php             # Статистика сайта
│   │   ├── UserFolderRepository.php        # Пользовательские папки
│   │   │   ├── ApplicationsTrait.php       # Заявки в папках
│   │   │   ├── ElementsTrait.php           # Элементы папки
│   │   │   ├── EntitiesTrait.php           # Сущности
│   │   │   ├── FoldersTrait.php            # Операции с папками
│   │   │   ├── ItemsTrait.php              # Элементы
│   │   │   ├── MoveTrait.php               # Перемещение элементов
│   │   │   ├── StructureTrait.php          # Дерево структуры
│   │   │   └── SubscriptionsTrait.php      # Подписки на папки
│   │   └── UserRepository.php              # Пользователи
│   │       ├── FindTrait.php               # Поиск пользователей
│   │       └── UpdateTrait.php             # Обновление профиля
│   ├── Service/
│   │   └── ImageService.php    # Обработка и ресайз изображений (Intervention Image)
│   ├── Services/
│   │   ├── AuthService.php     # Бизнес-логика авторизации (логин, регистрация, JWT)
│   │   ├── RateLimitService.php # Rate limiting (счётчики, блокировки, капча)
│   │   └── TurnstileService.php # Верификация Cloudflare Turnstile
│   └── Validators/
│       ├── ApplicationValidator.php # Валидация заявок
│       └── UserValidator.php        # Валидация данных пользователя
│
├── config/
│   ├── app.php                 # Конфигурация приложения (JWT, rate limits, uploads, API ключи)
│   └── database.php            # Подключение к БД (MySQL/MariaDB)
│
├── routes/
│   ├── web.php                 # Веб-маршруты (HTML-страницы)
│   ├── api.php                 # API-маршруты (JSON) + маршрут /item/:id
│   ├── web-item.php            # Доп. маршрут страницы элемента (справочный)
│   └── api-server-ping.php     # Доп. маршруты пинга серверов (справочный)
│
├── public/                     # Публичная директория (document root)
│   ├── index.php               # Единая точка входа (front controller)
│   ├── .htaccess               # Apache rewrite rules
│   ├── css/
│   │   ├── app.css             # Главный CSS-файл (импорт всех стилей)
│   │   ├── reset.css           # CSS reset
│   │   ├── root.css            # CSS-переменные (цвета, шрифты, тема)
│   │   ├── layout.css          # Общая сетка и layout
│   │   ├── responsive.css      # Адаптивные стили
│   │   ├── utils.css           # Утилитарные классы
│   │   ├── components/         # Стили компонентов
│   │   │   ├── application-cards.css   # Карточки заявок
│   │   │   ├── application-form.css    # Форма заявки
│   │   │   ├── application-modal.css   # Модальное окно заявки
│   │   │   ├── buttons.css             # Кнопки
│   │   │   ├── cards.css               # Карточки
│   │   │   ├── forms.css               # Формы
│   │   │   ├── image-editor.css        # Редактор изображений
│   │   │   ├── modal.css               # Модальные окна
│   │   │   ├── modpack-selector.css    # Селектор модпаков
│   │   │   └── navigation.css          # Навигация
│   │   └── sections/           # Стили секций/страниц
│   │       ├── admin.css       # Админ-панель
│   │       ├── auth.css        # Авторизация
│   │       ├── chat.css        # Чат
│   │       ├── community.css   # Сообщества
│   │       ├── feed.css        # Лента заявок
│   │       ├── hero.css        # Hero-секция
│   │       ├── item-page.css   # Страница элемента
│   │       ├── my-folder.css   # Папка пользователя
│   │       ├── popular.css     # Популярные модпаки
│   │       ├── profile.css     # Профиль
│   │       ├── server-status.css # Статус сервера
│   │       └── settings.css    # Настройки
│   └── js/
│       ├── app.js              # Главный JS (инициализация, тема, навигация)
│       ├── feed-sort.js        # Сортировка ленты заявок
│       ├── lightbox.js         # Просмотр изображений (lightbox)
│       ├── mobile-nav.js       # Мобильная навигация
│       ├── modals.js           # Управление модальными окнами
│       ├── notifications.js    # Система уведомлений
│       ├── password-toggle.js  # Показ/скрытие пароля
│       ├── theme.js            # Переключение тем (светлая/тёмная)
│       ├── view-mode.js        # Переключение вида (сетка/список)
│       ├── initSaveColors.js   # Сохранение пользовательских цветов
│       ├── forms/              # Обработчики форм
│       │   ├── handleForm.js           # Универсальный обработчик форм (fetch + CSRF)
│       │   ├── application-delete.js   # Удаление заявки
│       │   ├── home-application.js     # Заявка с главной
│       │   ├── login.js                # Форма логина
│       │   ├── modpack-apply.js        # Заявка на модпак
│       │   └── register.js             # Форма регистрации
│       ├── image-editor/       # Редактор изображений (кроп аватара/баннера)
│       │   ├── index.js        # Точка входа
│       │   ├── ImageEditor.js  # Основной класс
│       │   ├── CropHandler.js  # Обрезка
│       │   ├── ModalUI.js      # UI модального окна
│       │   └── PreviewManager.js # Превью
│       ├── modpack-selector/   # Выбор модпака (поиск + список)
│       │   ├── index.js        # Точка входа
│       │   ├── api.js          # API-запросы
│       │   └── render.js       # Рендеринг списка
│       └── modules/
│           └── modal.js        # Модуль модальных окон
│
├── templates/                  # PHP-шаблоны (View)
│   ├── layouts/
│   │   ├── main.php            # Основной layout (header + footer + контент)
│   │   └── auth.php            # Layout для страниц авторизации
│   ├── components/             # Переиспользуемые компоненты
│   │   ├── header.php          # Шапка сайта (навигация, аватар, уведомления)
│   │   ├── footer.php          # Подвал сайта
│   │   ├── icons.php           # SVG-иконки
│   │   ├── additional-icons.php # Дополнительные иконки
│   │   ├── modal.php           # Базовый компонент модального окна
│   │   ├── pagination.php      # Пагинация
│   │   ├── user-avatar.php     # Аватар пользователя
│   │   ├── turnstile-captcha.php # Cloudflare Turnstile капча
│   │   ├── application-card/   # Карточка заявки
│   │   │   ├── card.php                # Основной шаблон карточки
│   │   │   ├── card-header.php         # Заголовок (пользователь, дата)
│   │   │   ├── card-message.php        # Текст заявки
│   │   │   ├── card-contacts.php       # Контакты (Discord, Telegram, VK)
│   │   │   ├── card-images.php         # Изображения
│   │   │   ├── card-relevant.php       # Срок актуальности
│   │   │   ├── card-status.php         # Статус заявки
│   │   │   ├── card-footer.php         # Подвал карточки
│   │   │   └── js/                     # Встроенные JS для карточки
│   │   ├── application-form/   # Форма создания заявки
│   │   ├── application-modal/  # Модальное окно заявки (создание/редактирование)
│   │   └── modpack-selector/   # Компонент выбора модпака
│   ├── pages/                  # Шаблоны страниц
│   │   ├── feed/               # Главная — лента заявок
│   │   ├── auth/               # Логин, регистрация, восстановление пароля
│   │   ├── modpacks/           # Каталог модпаков (Modrinth / CurseForge)
│   │   ├── modpack/            # Карточка конкретного модпака
│   │   ├── profile/            # Профиль пользователя (/@username)
│   │   │   └── index/tabs-container/tab-content/
│   │   │       ├── folder-tab/         # Вкладка «Моя папка» (дерево файлов)
│   │   │       └── subscriptions-tab.php # Вкладка подписок
│   │   ├── item/               # Страница элемента папки (/item/:id)
│   │   ├── chat/               # Страница чата
│   │   ├── settings/           # Настройки профиля
│   │   ├── admin/              # Админ-панель (модерация заявок)
│   │   ├── captcha-required.php # Страница «Требуется капча»
│   │   └── error.php           # Страница ошибки
│   └── errors/
│       └── 404.php             # Страница 404
│
├── migrations/                 # SQL-миграции
│   ├── 001_create_tables.sql   # Создание всех таблиц (основная миграция)
│   ├── 002_Indexes_of_stored_tables.sql    # Индексы
│   ├── 003_AUTO_INCREMENTS.sql             # Автоинкременты
│   ├── 004_Foreign_key_constraints_of_stored_tables.sql # Внешние ключи
│   └── 005_add_folder_item_id_to_applications.sql       # Доп. миграция
│
├── cron/
│   └── server-ping.php         # Cron-задача: периодический пинг Minecraft-серверов
│
├── scripts/
│   └── cleanup-rate-limits.php # Скрипт очистки устаревших записей rate limiting
│
├── storage/                    # Хранилище (логи, кэш, временные файлы)
├── composer.json               # PHP-зависимости
└── .env.example                # Пример переменных окружения
```

## Веб-страницы (маршруты)

| URL | Контроллер | Описание |
|-----|-----------|----------|
| `/` | `Web\HomeController` | Главная — лента заявок на совместную игру |
| `/modrinth` | `Web\ModpackController` | Каталог модпаков Modrinth |
| `/curseforge` | `Web\ModpackController` | Каталог модпаков CurseForge |
| `/modpack/:platform/:slug` | `Web\ModpackController` | Страница конкретного модпака |
| `/login` | `Web\AuthController` | Страница входа (только для гостей) |
| `/register` | `Web\AuthController` | Страница регистрации (только для гостей) |
| `/forgot-password` | `Web\AuthController` | Восстановление пароля |
| `/logout` | `Web\AuthController` | Выход из аккаунта |
| `/@:username` | `Web\ProfileController` | Профиль пользователя |
| `/@:username/my_folder` | `Web\ItemController` | Корневая папка пользователя |
| `/@:username/:slug` | `Web\ItemController` | Элемент папки по slug в контексте пользователя |
| `/settings` | `Web\SettingsController` | Настройки профиля (требует авторизации) |
| `/chat/:chatId` | `Web\ChatController` | Страница чата (требует авторизации) |
| `/admin` | `Web\AdminController` | Админ-панель (только для админов) |
| `/item/:slug` | `Web\ItemController` | Страница элемента папки (по slug, прямая ссылка) |
| `/item/:id` | `Web\ItemController` | Обратная совместимость (редирект на slug) |

## API-эндпоинты

### Публичные
- `POST /api/auth/login` — Авторизация
- `POST /api/auth/register` — Регистрация
- `POST /api/auth/refresh` — Обновление JWT-токена
- `GET /api/modpack-selector/*` — Поиск и список модпаков для селектора
- `GET /api/user-folder/public/*` — Публичные данные папок пользователей
- `GET /api/server-ping` — Пинг сервера
- `GET /api/server-ping/history` — История пингов
- `POST /api/captcha/solve` — Решение капчи

### Требуют авторизации
- `PUT|POST /api/user/update` — Обновление профиля
- `POST /api/user/avatar` — Загрузка аватара
- `POST /api/user/banner` — Загрузка баннера
- `POST /api/user/password` — Смена пароля
- `POST /api/modpack/apply` — Подача заявки на модпак
- `POST /api/application/update` — Редактирование заявки
- `DELETE /api/application/delete/:id` — Удаление заявки
- `GET /api/notifications` — Получение уведомлений
- `GET /api/chat/messages` — Сообщения чата
- `POST /api/chat/send` — Отправка сообщения
- `POST /api/chat/poll/create` — Создание опроса
- `GET /api/user-folder/structure` — Структура папок
- `POST /api/user-folder/create|update|delete|move` — Управление элементами папки

### Только для админов
- `GET /api/admin/application/:id` — Детали заявки
- `POST /api/admin/application/status` — Изменение статуса заявки
- `POST /api/admin/application/delete` — Удаление заявки админом

## Таблицы базы данных

| Таблица | Назначение |
|---------|-----------|
| `users` | Пользователи (профиль, роли, настройки темы/цветов) |
| `modpacks` | Кэш модпаков (Modrinth / CurseForge) |
| `modpack_applications` | Заявки на совместную игру |
| `application_images` | Изображения к заявкам |
| `application_folder_links` | Связь заявок с элементами папок |
| `user_folder_items` | Элементы пользовательских папок (дерево) |
| `user_folder_subscriptions` | Подписки на папки пользователей |
| `folder_paths` | Closure table для иерархии папок |
| `user_servers` | Minecraft-серверы пользователей |
| `user_shortcuts` | Ссылки-ярлыки в папках |
| `server_ping_history` | История пингов серверов |
| `chat_messages` | Сообщения чатов |
| `chat_message_images` | Изображения в сообщениях |
| `chat_message_likes` | Лайки сообщений |
| `chat_polls` / `chat_poll_options` / `chat_poll_votes` | Опросы в чатах |
| `communities` | Сообщества пользователей |
| `community_chats` / `community_folders` | Чаты и папки сообществ |
| `community_subscribers` / `community_moderators` / `community_bans` | Участники и модерация |
| `notifications` | Уведомления |
| `refresh_tokens` | Refresh JWT-токены |
| `auth_logs` | Логи авторизации |
| `password_resets` | Токены сброса пароля |
| `rate_limit_counters` / `rate_limit_blocks` / `rate_limit_violations` | Rate limiting |
| `site_stats` | Счётчики статистики сайта |

## Архитектурные особенности

- **Роутинг**: собственный роутер (`App\Http\Router`) со статическими методами `get/post/put/delete`, поддержкой групп, префиксов, middleware и параметров (`:id`, `:slug`). Метод `where()` маршрута задаёт regex-ограничение на параметр, которое применяется при построении паттерна в `buildPattern()`.
- **Middleware**: цепочка — `auth`, `guest`, `admin`, `csrf`, `rate_limit:N,T`
- **Шаблоны**: чистый PHP с `render()` / `view()`, layouts + вложенные компоненты
- **Repository pattern**: вся работа с БД через репозитории с trait-ами для группировки логики
- **Пользовательские папки**: иерархическая структура (closure table `folder_paths`), типы элементов: `folder`, `server`, `shortcut`, `modpack`, `mod`, `application`, `chat`
- **Серверный пинг**: клиентский + серверный (cron) пинг Minecraft-серверов с историей
- **Безопасность**: CSRF-токены, bcrypt-пароли, JWT с версионированием, rate limiting с капчей, XSS-экранирование через `e()`

## Система модальных окон (конструктор)

Все модальные окна на сайте **должны** работать через единую систему (`public/js/modules/modal.js`). Это обеспечивает единообразное поведение: анимации, закрытие по Escape, клик по backdrop, фокус первого поля, блокировку скролла.

### API единой системы модалок

| Функция | Описание |
|---------|----------|
| `openModal(modal)` | Открыть модалку (элемент или ID строкой) |
| `closeModal(modal)` | Закрыть модалку с анимацией |
| `loadModal(url, options)` | Загрузить модалку с сервера и открыть |
| `lockBodyScroll()` | Заблокировать скролл body |
| `unlockBodyScroll()` | Разблокировать скролл body |

### Data-атрибуты для триггеров

- `data-modal-open="modalId"` — открытие существующей модалки по ID
- `data-modal-load="url"` — динамическая загрузка модалки с сервера
- `data-modal-close` или `data-close` — закрытие текущей модалки

### Создание новой модалки

Любая модалка должна следовать структуре:
```html
<div class="modal" id="myModal" style="display:none;">
    <div class="modal-backdrop">
        <div class="modal-content modal-sm|modal-md|modal-lg">
            <div class="modal-header">
                <h3>Заголовок</h3>
                <button data-modal-close>×</button>
            </div>
            <div class="modal-body">...</div>
            <div class="modal-footer">...</div>
        </div>
    </div>
</div>
```

Открытие: через `data-modal-open="myModal"` на кнопке или `openModal('myModal')` из JS.

## UUID-hash ссылки на элементы (slug)

Каждый элемент папки (`user_folder_items`) имеет поле `slug` — уникальный короткий идентификатор, генерируемый при создании (хэш из ID + время + тип + имя).

### Формат URL

| URL | Описание |
|-----|----------|
| `/@:username/:prefix-:slug` | Страница элемента в контексте пользователя |
| `/item/:prefix-:slug` | Прямая ссылка (для копирования/шеринга) |
| `/@:username/my_folder` | Корневая папка пользователя |
| `/item/:id` | Обратная совместимость (редирект на slug) |

### Префиксы типов (неизменяемые)

| Тип элемента | Префикс |
|-------------|---------|
| `folder` | `folder-` |
| `server` | `server-` |
| `shortcut` | `shortcut-` |
| `mod` | `mod-` |
| `modpack` | `modpack-` |
| `chat` | `chat-` |
| `application` | `string-` |

Префикс типа нельзя изменить. Slug (часть после префикса) можно изменить в настройках элемента. Валидация slug: минимум 3 символа, латиница + цифры + дефис + подчёркивание, проверка по `login-reserved.txt`, уникальность.

### Важно для разработки

- Константа `SLUG_PREFIXES` определена в `UserFolderRepository` и `ItemsTrait`
- Метод `getFullSlug($type, $slug)` возвращает полный slug с префиксом
- Метод `getChildrenByParent($parentId)` — для публичного получения дочерних элементов (без user_id)
- Метод `getChildren($userId, $parentId)` — для авторизованных запросов (с проверкой user_id)
- Счётчики в footer (`site_stats`) автоинициализируются при первом запросе, если таблица пуста
- API-маршруты для заявок: `/api/application/:id/toggle-hidden` (POST), `/api/application/delete/:id` (DELETE) — ID передаётся в URL, не в теле запроса
