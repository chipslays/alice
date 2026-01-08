<?php

namespace Alice\Types\Nlu\Intents;

use Alice\Support\Container;

class Intent
{
    public function __construct(protected array $data)
    {
        //
    }

    public function slot(string $name, ?array $default = null): ?Slot
    {
        if (isset($this->data['slots'][$name])) {
            return new Slot($this->data['slots'][$name]);
        } else {
            return Container::getInstance()->call($default);
        }
    }
}