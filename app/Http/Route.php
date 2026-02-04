<?php

declare(strict_types=1);

namespace App\Http;

class Route
{
    use \App\Http\Route\ParamsTrait;
    use \App\Http\Route\MatchingTrait;
    use \App\Http\Route\HandlerTrait;
    use \App\Http\Route\MiddlewareTrait;

    private string $method;
    private string $path;
    private $handler;
    private array $middleware = [];
    private array $params = [];
	protected $constraints = [];

    public function __construct(string $method, string $path, $handler)
    {
        $this->method = $method;
        $this->path = $path;
        $this->handler = $handler;
        $this->parsePathParams();
    }

	public function where(string $parameter, string $pattern)
    {
        $this->constraints[$parameter] = $pattern;
        return $this;
    }

}
