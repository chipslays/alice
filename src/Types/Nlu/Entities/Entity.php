<?php

namespace Alice\Types\Nlu\Entities;

use Alice\Support\Container;

/**
 * Базовый класс сущности NLU (например, YANDEX.NUMBER, YANDEX.GEO и др.).
 */
class Entity
{
    public protected(set) string $type;

    public protected(set) string $start;

    public protected(set) string $end;

    /**
     * @param array $data Данные сущности
     */
    public function __construct(protected array $data)
    {
        $this->type = $data['type'];
        $this->start = $data['tokens']['start'];
        $this->end = $data['tokens']['end'];
    }

    /**
     * Получить значение сущности или конкретного ключа.
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function value(?string $key = null, mixed $default = null): mixed
    {
        // Например, `YANDEX.NUMBER` имеет в поле `value` число, а не массив.
        if (!is_array($this->data['value'])) {
            return $this->data['value'];
        }

        return $this->data['value'][$key] ?? Container::getInstance()->call($default);
    }
}