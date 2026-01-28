<?php

declare(strict_types=1);

namespace App\Http\Route;

use App\Http\Request;
use App\Http\Response;

trait HandlerTrait
{
    /**
     * Вызывает обработчик маршрута
     */
    public function call(Request $request, array $params = []): void
    {
        if (is_array($this->handler)) {
            $this->callControllerMethod($request, $params);
            return;
        }

        if (is_callable($this->handler)) {
            $this->callCallable($request, $params);
            return;
        }
    }

    private function callControllerMethod(Request $request, array $params): void
    {
        [$class, $method] = $this->handler;
        $controller = new $class();
        $result = $controller->$method($request, ...array_values($params));
        
        $this->handleResult($result);
    }

    private function callCallable(Request $request, array $params): void
    {
        $result = call_user_func(
            $this->handler, 
            $request, 
            ...array_values($params)
        );
        
        $this->handleResult($result);
    }

    private function handleResult($result): void
    {
        if ($result instanceof Response) {
            return;
        }
        
        if (is_array($result)) {
            Response::json($result);
        }
    }
}
