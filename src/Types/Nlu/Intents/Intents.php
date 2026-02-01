<?php

namespace Alice\Types\Nlu\Intents;

use Alice\Support\Container;

class Intents
{
    /**
     * Конструктор списка интентов.
     *
     * @param array $intents Массив интентов
     */
    public function __construct(protected array $intents = [])
    {
        //
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
            return new Intent($this->intents[$name]);
        } else {
            return Container::getInstance()->call($default);
        }
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

    /**
     * Возвращает все интенты как массив.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->toArray();
    }

    /**
     * Преобразует интенты в массив.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->intents;
    }
}