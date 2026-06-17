<?php

namespace Alice\Events;

use Alice\Support\Container;
use Alice\Contracts\Eventable;
use Closure;

/**
 * Представление события с правилами, обработчиком и middleware/priority.
 */
class Event
{
    public const int PRIORITY_HIGHEST = 1_000_000;
    public const int PRIORITY_HIGH = 500_000;
    public const int PRIORITY_NORMAL = 0;
    public const int PRIORITY_LOW = -500_000;
    public const int PRIORITY_LOWEST = -1_000_000;

    public int $priority = self::PRIORITY_NORMAL;

    /** @var array<int, Closure|array<int,mixed>|string> */
    public array $middleware = [];

    /**
     * @param Closure|array<int|string,mixed>|string $rules Правила сопоставления
     * @param Closure|array<int,mixed>|string $handler Обработчик события
     */
    public function __construct(
        public readonly Eventable $eventable,
        public readonly Closure|array|string $rules,
        public readonly Closure|array|string $handler
    ) {
        //
    }

    /**
     * Устанавливает приоритет события.
     *
     * @param int $priority
     * @return static
     */
    public function priority(int $priority = self::PRIORITY_NORMAL): static
    {
        $this->priority = $priority;
        return $this;
    }

    /**
     * Добавляет middleware для события.
     *
     * @param array<int, Closure|array<int,mixed>|string>|string|Closure $middlewares
     * @return static
     */
    public function middleware(array|string|Closure $middlewares): static
    {
        $middlewares = is_array($middlewares) ? $middlewares : [$middlewares];
        $this->middleware = array_merge($this->middleware, $middlewares);
        return $this;
    }

    /**
     * Позволяет добавить альтернативные правила для одного обработчика.
     *
     * @param Closure $callback
     * @return static
     */
    public function or(Closure $callback): static
    {
        $callback($this->eventable, $this->handler);

        return $this;
    }
}
