<?php

namespace Alice\Support;

/**
 * Менеджер ассетов (картинок/аудио) с простым набором статических методов.
 */
class Assets
{
    /** @var array<string, mixed> */
    protected static array $items = [];

    /**
     * Загрузить набор ассетов.
     *
     * @param array<string,mixed> $items
     * @return void
     */
    public static function load(array $items): void
    {
        static::$items = $items;
    }

    /**
     * Установить значение для алиаса ассета.
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
     * Получить ассет по алиасу или вернуть значение по умолчанию.
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
     * Проверить наличие ассета по алиасу.
     *
     * @param string $alias
     * @return bool
     */
    public static function has(string $alias): bool
    {
        return array_key_exists($alias, static::$items);
    }

    /**
     * Удалить ассет по алиасу.
     *
     * @param string $alias
     * @return void
     */
    public static function remove(string $alias): void
    {
        unset(static::$items[$alias]);
    }

    /**
     * Количество ассетов.
     *
     * @return int
     */
    public static function count(): int
    {
        return count(static::$items);
    }

    /**
     * Получить все ассеты в виде массива.
     *
     * @return array<string,mixed>
     */
    public static function all(): array
    {
        return static::toArray();
    }

    /**
     * Преобразовать в массив.
     *
     * @return array<string,mixed>
     */
    public static function toArray(): array
    {
        return static::$items;
    }
}