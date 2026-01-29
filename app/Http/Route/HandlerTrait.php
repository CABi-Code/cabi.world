<?php

declare(strict_types=1);


namespace App\Http\Route;

use App\Http\Request;
use App\Http\Response;
use ReflectionMethod;
use ReflectionNamedType;

trait HandlerTrait
{
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
        
        // Приводим параметры к нужным типам через рефлексию
        $castedParams = $this->castParams($class, $method, $params);
        $result = $controller->$method($request, ...$castedParams);
        
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

    /**
     * Приводит параметры к типам, указанным в сигнатуре метода
     */
    private function castParams(string $class, string $method, array $params): array
    {
        $reflection = new \ReflectionMethod($class, $method);
        $methodParams = $reflection->getParameters();
        
        // Пропускаем первый параметр (Request)
        $methodParams = array_slice($methodParams, 1);
        $paramValues = array_values($params);
        $result = [];
        
        foreach ($methodParams as $index => $param) {
            $value = $paramValues[$index] ?? null;
            
            if ($value === null && $param->isDefaultValueAvailable()) {
                $result[] = $param->getDefaultValue();
                continue;
            }
            
            $type = $param->getType();
            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin() === false) {
                $typeName = $type->getName();
                $result[] = match($typeName) {
                    'int' => (int)$value,
                    'float' => (float)$value,
                    'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                    'string' => (string)$value,
                    'array' => (array)$value,
                    default => $value
                };
            } else {
                $result[] = $value;
            }
        }
        
        return $result;
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
