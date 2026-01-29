<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Http\Request;
use App\Http\Response;
use App\Repository\ApplicationRepository;
use App\Repository\NotificationRepository;

class AdminController
{
    private ApplicationRepository $appRepo;
    private NotificationRepository $notifRepo;

    public function __construct()
    {
        $this->appRepo = new ApplicationRepository();
        $this->notifRepo = new NotificationRepository();
    }

    /**
     * Получить детали заявки (JSON)
     */
    public function getApplication(Request $request, int $id): void
    {
        $application = $this->appRepo->findById($id);
        
        if (!$application) {
            Response::error('Application not found', 404);
            return;
        }
        
        $images = $this->appRepo->getImages($id);
        $application['images'] = $images;
        
        Response::json([
            'success' => true,
            'application' => $application
        ]);
    }

    /**
     * Получить модалку деталей заявки (HTML)
     */
    public function getApplicationModal(Request $request, int $id): void
    {
        $app = $this->appRepo->findById($id);
        
        if (!$app) {
            http_response_code(404);
            echo '<div class="modal-error">Заявка не найдена</div>';
            return;
        }
        
        $app['images'] = $this->appRepo->getImages($id);
        
        // Рендерим модалку
        ob_start();
        include TEMPLATES_PATH . '/pages/admin/modals/app-details.php';
        $html = ob_get_clean();
        
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
    }

    /**
     * Установить статус заявки
     */
    public function setApplicationStatus(Request $request): void
    {
        $appId = (int)($request->get('id', 0));
        $status = $request->get('status', '');
        
        if ($appId <= 0) {
            Response::error('Invalid ID', 400);
            return;
        }
        
        if (!in_array($status, ['pending', 'accepted', 'rejected'])) {
            Response::error('Invalid status', 400);
            return;
        }
        
        $application = $this->appRepo->findById($appId);
        
        if (!$application) {
            Response::error('Application not found', 404);
            return;
        }
        
        $result = $this->appRepo->setStatusByModerator($appId, $status);
        
        if ($result) {
            if ($status === 'accepted') {
                $this->notifRepo->notifyApplicationAccepted(
                    $application['user_id'],
                    $application['modpack_name'],
                    $application['slug'],
                    $application['platform']
                );
            } elseif ($status === 'rejected') {
                $this->notifRepo->notifyApplicationRejected(
                    $application['user_id'],
                    $application['modpack_name']
                );
            }
            
            Response::json(['success' => true]);
        } else {
            Response::error('Failed to update status', 500);
        }
    }

    /**
     * Удалить заявку
     */
    public function deleteApplication(Request $request): void
    {
        $appId = (int)($request->get('id', 0));
        
        if ($appId <= 0) {
            Response::error('Invalid ID', 400);
            return;
        }
        
        $result = $this->appRepo->deleteById($appId);
        
        if ($result) {
            Response::json(['success' => true]);
        } else {
            Response::error('Failed to delete', 500);
        }
    }
}
