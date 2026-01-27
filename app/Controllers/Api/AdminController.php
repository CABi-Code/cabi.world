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
            // Отправляем уведомление пользователю
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

    public function deleteApplication(Request $request): void
    {
        $appId = (int)($request->get('id', 0));
        
        if ($appId <= 0) {
            Response::error('Invalid ID', 400);
            return;
        }
        
        $application = $this->appRepo->findById($appId);
        
        if (!$application) {
            Response::error('Application not found', 404);
            return;
        }
        
        $this->appRepo->delete($appId, $application['user_id']);
        Response::json(['success' => true]);
    }
}
