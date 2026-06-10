<?php

namespace Alice\Types\Nlu\Intents;

use Alice\Support\Container;

class Intents
{
    /**
     * Конструктор списка интентов.
     *
     * @param array<int,mixed> $intents Массив интентов
     */
    public function __construct(protected array $intents = [])
    {
        //
    }

    /**
     * Возвращает все интенты как массив.
     *
     * @return array<int,mixed>
     */
    public function all(): array
    {
        return $this->toArray();
    }

    /**
     * Преобразует интенты в массив.
     *
     * @return array<int,array<string,mixed>>
     */
    public function toArray(): array
    {
        return array_map(function ($item) {
            return is_array($item) ? $item : [];
        }, $this->intents);
    }

    /**
     * Получить интент по имени или вернуть значение по умолчанию.
     *
     * @param string $name Имя интента
     * @param mixed $default Значение по умолчанию или callable
     * @return Intent|null
     */
    public function get(string $name, mixed $default = null): ?Intent
    {
        if (isset($this->intents[$name])) {
            /** @var array<string,mixed> $intentData */
            $intentData = is_array($this->intents[$name]) ? $this->intents[$name] : [];
            return new Intent($intentData);
        }

        if ($default === null) {
            return null;
        }

        if (is_callable($default) || is_string($default) || is_array($default)) {
            /** @var Intent|null */
            $res = Container::getInstance()->call($default);
            return $res instanceof Intent ? $res : null;
        }

        return null;
    }

    /**
     * Возвращает количество интентов.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->intents);
    }
}
