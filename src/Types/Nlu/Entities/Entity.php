<?php

namespace Alice\Types\Nlu\Entities;

use Alice\Support\Container;

class Entity
{
    public protected(set) string $type;

    public protected(set) string $start;

    public protected(set) string $end;

    public function __construct(protected array $data)
    {
        $this->type = $data['type'];
        $this->start = $data['tokens']['start'];
        $this->end = $data['tokens']['end'];
    }

    public function value(?string $key = null, mixed $default = null): mixed
    {
        // Например, `YANDEX.NUMBER` имеет в поле `value` число, а не массив.
        if (!is_array($this->data['value'])) {
            return $this->data['value'];
        }

        return $this->data['value'][$key] ?? Container::getInstance()->call($default);
    }
}