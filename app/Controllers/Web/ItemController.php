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
     * Страница отдельного элемента
     */
    public function show(Request $request, int $itemId): void
    {
        $item = $this->folderRepo->getItem($itemId);
        
        if (!$item) {
            $this->notFound('Элемент не найден');
            return;
        }
        
        // Владелец элемента
        $owner = $this->userRepo->findById($item['user_id']);
        if (!$owner) {
            $this->notFound('Пользователь не найден');
            return;
        }
        
        // Текущий пользователь
        $user = $request->user();
        $isOwner = $user && $user['id'] === $owner['id'];
        
        // Путь к элементу
        $path = $this->folderRepo->getItemPath($itemId);
        
        // Дочерние элементы
        $children = [];
        if ($this->folderRepo->isEntity($item['item_type'])) {
            $children = $this->folderRepo->getChildren($itemId);
        }
        
        // Подписки пользователя (для сайдбара)
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
            ['name' => 'Моя папка', 'url' => '/@' . $owner['login'] . '?tab=folder'],
        ];
        
        // Путь к элементу (без текущего)
        foreach ($path as $p) {
            if ($p['id'] !== $item['id']) {
                $crumbs[] = [
                    'name' => $p['name'],
                    'url' => '/item/' . $p['id']
                ];
            }
        }
        
        // Текущий элемент (без ссылки)
        $crumbs[] = ['name' => $item['name'], 'url' => null];
        
        return $crumbs;
    }
}
