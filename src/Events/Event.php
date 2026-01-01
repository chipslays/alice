<?php

namespace Alice\Events;

use Closure;

class Event
{
    public int $priority = 0;

    public array $middleware = [];

    public function __construct(
        public readonly Closure|array|string $rules,
        public readonly Closure|array|string $handler
    ) {}

    public function priority(int $priority): static
    {
        $this->priority = $priority;
        return $this;
    }

    public function middleware(array|string|Closure $middlewares): static
    {
        $middlewares = is_array($middlewares) ? $middlewares : [$middlewares];
        $this->middleware = array_merge($this->middleware, $middlewares);
        return $this;
    }
}
