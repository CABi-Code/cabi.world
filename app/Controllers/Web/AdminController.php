<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Http\Request;
use App\Repository\ApplicationRepository;

class AdminController extends BaseController
{
    private ApplicationRepository $appRepo;

    public function __construct()
    {
        $this->appRepo = new ApplicationRepository();
    }

    public function index(Request $request): void
    {
        $user = $request->user();
        
        // Параметры фильтрации
        $status = $request->query('status');
        $page = max(1, (int)$request->query('page', 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        
        // Валидируем статус
        if ($status && !in_array($status, ['pending', 'accepted', 'rejected'])) {
            $status = null;
        }
        
        // Загружаем данные
        $applications = $this->appRepo->findAllForAdmin($perPage, $offset, $status);
        $totalCount = $this->appRepo->countAllForAdmin($status);
        $pendingCount = $this->appRepo->countPending();
        $totalPages = (int)ceil($totalCount / $perPage);
        
        $this->render('pages/admin/index', [
            'title' => 'Админ-панель — cabi.world',
            'user' => $user,
            'applications' => $applications,
            'pendingCount' => $pendingCount,
            'totalPages' => $totalPages,
            'page' => $page,
            'status' => $status,
        ]);
    }
}
