<?php

namespace Alice\Support;

/**
 * Коллекция предопределённых наборов кнопок (по алиасу).
 */
class Buttons
{
    protected static array $items = [];

    /**
     * Загрузить набор кнопок.
     *
     * @param array $items
     * @return void
     */
    public static function load(array $items): void
    {
        static::$items = $items;
    }

    /**
     * Установить набор кнопок по алиасу.
     *
     * @param string $alias
     * @param mixed $value
     * @return void
     */
    public static function set(string $alias, mixed $value): void
    {
        static::$items[$alias] = $value;
    }

    /**
     * Получить набор кнопок по алиасу.
     *
     * @param string $alias
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $alias, mixed $default = null): mixed
    {
        return static::$items[$alias] ?? $default;
    }

    /**
     * Проверить наличие набора кнопок по алиасу.
     *
     * @param string $alias
     * @return bool
     */
    public static function has(string $alias): bool
    {
        return isset($alias, static::$items);
    }

    /**
     * Удалить набор кнопок по алиасу.
     *
     * @param string $alias
     * @return void
     */
    public static function remove(string $alias): void
    {
        unset(static::$items[$alias]);
    }

    /**
     * Количество наборов кнопок.
     *
     * @return int
     */
    public static function count(): int
    {
        return count(static::$items);
    }

    /**
     * Получить все наборы кнопок.
     *
     * @return array
     */
    public static function all(): array
    {
        return static::toArray();
    }

    /**
     * Преобразовать в массив.
     *
     * @return array
     */
    public static function toArray(): array
    {
        return static::$items;
    }
}