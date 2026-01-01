<?php

declare(strict_types=1);

namespace Alice\Support;

class Defer
{
    /**
     * @var array<int, list<array{callback: callable|string|array, arguments: array}>>
     */
    protected static array $callbacks = [];

    /**
     * Добавляет колбэк в очередь выполнения.
     *
     * @param callable|string|array $callback
     * @param array $arguments
     * @param int $priority Чем БОЛЬШЕ число, тем РАНЬШЕ выполнится (по умолчанию 0).
     */
    public static function add(callable|string|array $callback, array $arguments = [], int $priority = 0): void
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
