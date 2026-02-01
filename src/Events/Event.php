<?php

namespace Alice\Events;

use Closure;

/**
 * Представление события с правилами, обработчиком и middleware/priority.
 */
class Event
{
    public int $priority = 0;

    /** @var array<int, Closure|array<int,mixed>|string> */
    public array $middleware = [];

    /**
     * @param Closure|array<int|string,mixed>|string $rules Правила сопоставления
     * @param Closure|array<int,mixed>|string $handler Обработчик события
     */
    public function __construct(
        public readonly Closure|array|string $rules,
        public readonly Closure|array|string $handler
    ) {}

    /**
     * Устанавливает приоритет события.
     *
     * @param int $priority
     * @return $this
     */
    public function priority(int $priority): static
    {
        $this->priority = $priority;
        return $this;
    }

    /**
     * Добавляет middleware для события.
     *
     * @param array<int, Closure|array<int,mixed>|string>|string|Closure $middlewares
     * @return $this
     */
    public function middleware(array|string|Closure $middlewares): static
    {
        $middlewares = is_array($middlewares) ? $middlewares : [$middlewares];
        $this->middleware = array_merge($this->middleware, $middlewares);
        return $this;
    }
}
