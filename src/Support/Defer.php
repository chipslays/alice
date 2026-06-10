<?php

declare(strict_types=1);

namespace Alice\Support;

/**
 * Очередь отложенных задач (deferred callbacks), выполняется в порядке приоритета.
 */
class Defer
{
    public const int PRIORITY_HIGHEST = 1_000_000;
    public const int PRIORITY_HIGH = 500_000;
    public const int PRIORITY_NORMAL = 0;
    public const int PRIORITY_LOW = -500_000;
    public const int PRIORITY_LOWEST = -1_000_000;

    /**
     * @var array<int, array<int, array{callback: callable|string|array<int,mixed>, arguments: array<int,mixed>}>>
     */
    protected static array $callbacks = [];

    /**
     * Добавляет колбэк в очередь выполнения.
     *
     * @param callable|string|array<int,mixed> $callback
     * @param array<int,mixed> $arguments
     * @param int $priority Чем БОЛЬШЕ число, тем РАНЬШЕ выполнится (по умолчанию 0).
     */
    public static function add(callable|string|array $callback, array $arguments = [], int $priority = self::PRIORITY_NORMAL): void
    {
        self::$callbacks[$priority][] = [
            'callback' => $callback,
            'arguments' => $arguments,
        ];
    }

    public static function run(): void
    {
        if (empty(self::$callbacks)) {
            return;
        }

        krsort(self::$callbacks);

        foreach (self::$callbacks as $priorityGroup) {
            foreach ($priorityGroup as $item) {
                Container::getInstance()->call($item['callback'], $item['arguments']);
            }
        }

        self::$callbacks = [];
    }
}
