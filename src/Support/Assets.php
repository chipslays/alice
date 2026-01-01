<?php

namespace Alice\Support;

class Assets
{
    protected static array $items = [];

    public static function load(array $items): void
    {
        static::$items = $items;
    }

    public static function set(string $alias, mixed $value): void
    {
        static::$items[$alias] = $value;
    }

    public static function get(string $alias, mixed $default = null): mixed
    {
        return static::$items[$alias] ?? $default;
    }

    public static function has(string $alias): bool
    {
        return isset($alias, static::$items);
    }

    public static function remove(string $alias): void
    {
        unset(static::$items[$alias]);
    }

    public static function count(): int
    {
        return count(static::$items);
    }

    public static function all(): array
    {
        return static::toArray();
    }

    public static function toArray(): array
    {
        return static::$items;
    }
}