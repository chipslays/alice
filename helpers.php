<?php

use Alice\Support\Collection;
use Alice\Support\Container;
use Alice\Support\Defer;

if (!function_exists('container')) {
    function container(?string $abstract = null): mixed
    {
        if (is_null($abstract)) {
            return Container::getInstance();
        }

        return Container::getInstance()->get($abstract);
    }
}

if (!function_exists('call')) {
    function call(mixed $handler, array $parameters = []): mixed
    {
        return Container::getInstance()->call($handler, $parameters);
    }
}

if (!function_exists('defer')) {
    function defer(callable|string|array $callback, array $arguments = [], int $priority = 0): void {
        Defer::add($callback, $arguments, $priority);
    }
}

if (!function_exists('collectable')) {
    function collectable(array $items = []): Collection
    {
        return new Collection($items);
    }
}

/**
 * @param int|float $count Число
 * @param array $forms Массив форм ['арбуз', 'арбуза', 'арбузов']
 * @param string|null $format Формат вывода (по умолчанию "%d %s")
 */
function plural(int|float $count, array $forms, ?string $format = null): string
{
    $n = abs((int) $count);

    $index = ($n % 10 == 1 && $n % 100 != 11)
        ? 0
        : ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20) ? 1 : 2);

    $word = $forms[$index];

    // Если формат не задан, возвращаем "число слово"
    if ($format === null) {
        return $count . ' ' . $word;
    }

    // Можно использовать кастомный формат, например: "%s" (только слово) или "<b>%d</b> %s"
    return sprintf($format, $count, $word);
}