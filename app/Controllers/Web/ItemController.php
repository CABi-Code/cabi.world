<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Http\Request;
use App\Repository\UserFolderRepository;
use App\Repository\UserRepository;

class ItemController extends BaseController
{
    private UserFolderRepository $folderRepo;
    private UserRepository $userRepo;

    public function __construct()
    {
        $this->folderRepo = new UserFolderRepository();
        $this->userRepo = new UserRepository();
    }

    /**
     * Страница элемента по числовому ID (обратная совместимость)
     * Редиректит на slug URL
     */
    public function show(Request $request, int $itemId): void
    {
        $item = $this->folderRepo->getItem($itemId);

        if (!$item) {
            $this->notFound('Элемент не найден');
            return;
        }

        // Если у элемента есть slug, редиректим на slug URL
        if (!empty($item['slug'])) {
            $fullSlug = UserFolderRepository::getFullSlug($item['item_type'], $item['slug']);
            header('Location: /item/' . $fullSlug, true, 301);
            exit;
        }

        // Fallback: показываем страницу напрямую
        $this->renderItemPage($request, $item);
    }

    /**
     * Страница элемента по slug (прямая ссылка /item/:slug)
     */
    public function showBySlug(Request $request, string $slug): void
    {
        // slug приходит с префиксом типа: folder-abc123
        $rawSlug = $this->extractSlugFromFull($slug);

        if (!$rawSlug) {
            $this->notFound('Элемент не найден');
            return;
        }

        $item = $this->folderRepo->getItemBySlug($rawSlug);

        if (!$item) {
            $this->notFound('Элемент не найден');
            return;
        }

        $this->renderItemPage($request, $item);
    }

    /**
     * Страница элемента по slug в контексте пользователя (/@user/:slug)
     */
    public function showByUserSlug(Request $request, string $username, string $slug): void
    {
        $owner = $this->userRepo->findByLogin($username);
        if (!$owner) {
            $this->notFound('Пользователь не найден');
            return;
        }

        $rawSlug = $this->extractSlugFromFull($slug);

        if (!$rawSlug) {
            $this->notFound('Элемент не найден');
            return;
        }

        $item = $this->folderRepo->getItemBySlugAndUser($rawSlug, $owner['id']);

        if (!$item) {
            $this->notFound('Элемент не найден');
            return;
        }

        $this->renderItemPage($request, $item, $owner);
    }

    /**
     * Страница "Моя папка" в обёртке item-page (/@user/my_folder)
     */
    public function myFolder(Request $request, string $username): void
    {
        $owner = $this->userRepo->findByLogin($username);
        if (!$owner) {
            $this->notFound('Пользователь не найден');
            return;
        }

        $user = $request->user();
        $isOwner = $user && $user['id'] === $owner['id'];

        // Корневые элементы пользователя
        $children = $this->folderRepo->getChildren($owner['id'], null);

        // Подписки
        $subscriptions = [];
        if ($user) {
            $subscriptions = $this->folderRepo->getUserSubscriptions($user['id'], 20);
        }

        $breadcrumbs = [
            ['name' => 'Главная', 'url' => '/'],
            ['name' => $owner['username'], 'url' => '/@' . $owner['login']],
            ['name' => 'Моя папка', 'url' => null],
        ];

        // Виртуальный элемент для страницы "Моя папка"
        $item = [
            'id' => 0,
            'user_id' => $owner['id'],
            'parent_id' => null,
            'item_type' => 'folder',
            'name' => 'Моя папка',
            'slug' => 'my_folder',
            'description' => null,
            'icon' => 'folder',
            'color' => '#eab308',
            'settings' => null,
            'is_hidden' => 0,
        ];

        $this->render('pages/item/index', [
            'title' => 'Моя папка — ' . $owner['username'] . ' — cabi.world',
            'item' => $item,
            'owner' => $owner,
            'user' => $user,
            'isOwner' => $isOwner,
            'path' => [],
            'children' => $children,
            'subscriptions' => $subscriptions,
            'breadcrumbs' => $breadcrumbs,
            'isMyFolder' => true,
        ]);
    }

    /**
     * Рендер страницы элемента
     */
    private function renderItemPage(Request $request, array $item, ?array $owner = null): void
    {
        if (!$owner) {
            $owner = $this->userRepo->findById($item['user_id']);
            if (!$owner) {
                $this->notFound('Пользователь не найден');
                return;
            }
        }

        $user = $request->user();
        $isOwner = $user && $user['id'] === $owner['id'];

        // Путь к элементу
        $path = $this->folderRepo->getItemPath($item['id']);

        // Дочерние элементы (исправлен баг: было getChildren($itemId) с неверными параметрами)
        $children = [];
        if ($this->folderRepo->isEntity($item['item_type'])) {
            $children = $this->folderRepo->getChildrenByParent($item['id']);
        }

        // Подписки пользователя
        $subscriptions = [];
        if ($user) {
            $subscriptions = $this->folderRepo->getUserSubscriptions($user['id'], 20);
        }

        // Хлебные крошки
        $breadcrumbs = $this->buildBreadcrumbs($owner, $path, $item);

        $this->render('pages/item/index', [
            'title' => $item['name'] . ' — cabi.world',
            'item' => $item,
            'owner' => $owner,
            'user' => $user,
            'isOwner' => $isOwner,
            'path' => $path,
            'children' => $children,
            'subscriptions' => $subscriptions,
            'breadcrumbs' => $breadcrumbs,
            'isMyFolder' => false,
        ]);
    }

    /**
     * Построить хлебные крошки
     */
    private function buildBreadcrumbs(array $owner, array $path, array $item): array
    {
        $crumbs = [
            ['name' => 'Главная', 'url' => '/'],
            ['name' => $owner['username'], 'url' => '/@' . $owner['login']],
            ['name' => 'Моя папка', 'url' => '/@' . $owner['login'] . '/my_folder'],
        ];

        // Путь к элементу (без текущего)
        foreach ($path as $p) {
            if ($p['id'] !== $item['id']) {
                $slug = $p['slug'] ?? null;
                if ($slug) {
                    $fullSlug = UserFolderRepository::getFullSlug($p['item_type'], $slug);
                    $url = '/@' . $owner['login'] . '/' . $fullSlug;
                } else {
                    $url = '/item/' . $p['id'];
                }
                $crumbs[] = [
                    'name' => $p['name'],
                    'url' => $url
                ];
            }
        }

        // Текущий элемент (без ссылки)
        $crumbs[] = ['name' => $item['name'], 'url' => null];

        return $crumbs;
    }

    /**
     * Извлечь чистый slug из полного slug с префиксом
     * Например: folder-abc123 → abc123
     */
    private function extractSlugFromFull(string $fullSlug): ?string
    {
        $prefixes = UserFolderRepository::SLUG_PREFIXES;
        foreach ($prefixes as $type => $prefix) {
            if (str_starts_with($fullSlug, $prefix)) {
                $rawSlug = substr($fullSlug, strlen($prefix));
                if ($rawSlug !== '' && $rawSlug !== false) {
                    return $rawSlug;
                }
            }
        }
        return null;
    }
}
