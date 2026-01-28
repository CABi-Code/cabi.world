<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Repository\NotificationRepository;

abstract class BaseController
{
    /**
     * Рендерит страницу с layout
     * 
     * @param string $template Путь к шаблону относительно TEMPLATES_PATH
     * @param array $data Данные для передачи в шаблон
     * @param string $layout Layout для использования
     */
    protected function render(string $template, array $data = [], string $layout = 'layouts/main'): void
    {
        // Добавляем счётчик уведомлений если есть пользователь
        if (isset($data['user']) && $data['user'] && !isset($data['unreadNotifications'])) {
            $notifRepo = new NotificationRepository();
            $data['unreadNotifications'] = $notifRepo->countUnread($data['user']['id']);
        }
        
        // Извлекаем переменные для шаблона
        extract($data);
        
        // Рендерим контент страницы
        ob_start();
        require TEMPLATES_PATH . '/' . $template . '.php';
        $content = ob_get_clean();
        
        // Рендерим layout с контентом
        require TEMPLATES_PATH . '/' . $layout . '.php';
    }

    /**
     * Рендерит только шаблон без layout
     */
    protected function renderPartial(string $template, array $data = []): string
    {
        extract($data);
        
        ob_start();
        require TEMPLATES_PATH . '/' . $template . '.php';
        return ob_get_clean();
    }

    /**
     * Показывает страницу ошибки 404
     */
    protected function notFound(string $message = 'Страница не найдена'): void
    {
        http_response_code(404);
        $this->render('errors/404', [
            'title' => 'Не найдено',
            'message' => $message
        ]);
    }

    /**
     * Показывает страницу ошибки 403
     */
    protected function forbidden(string $message = 'Доступ запрещён'): void
    {
        http_response_code(403);
        $this->render('errors/403', [
            'title' => 'Доступ запрещён',
            'message' => $message
        ]);
    }
}
