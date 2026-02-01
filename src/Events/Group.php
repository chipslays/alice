<?php

namespace Alice\Events;

use Closure;

class Group
{
    /**
     * @param Event[] $events Ссылки на созданные события
     */
    public function __construct(
        private readonly array $events
    ) {}

    /**
     * Добавить middleware ко всем событиям в этой группе.
     *
     * @param array<int, Closure|array<int,mixed>|string>|string|Closure $middleware
     * @return $this
     */
    public function middleware(array|string|Closure $middleware): self
    {
        foreach ($this->events as $event) {
            $event->middleware($middleware);
        }
        return $this;
    }


    /**
     * Установить приоритет всем событиям группы.
     *
     * @param int $priority
     * @return $this
     */
    public function priority(int $priority): self
    {
        foreach ($this->events as $event) {
            $event->priority($priority);
        }
        return $this;
    }
}
