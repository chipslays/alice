<?php

namespace Alice\Types\Nlu\Intents;

use Alice\Support\Container;

/**
 * Класс одного интента (намерения) с доступом к слотам.
 */
class Intent
{
    /**
     * @param array $data
     */
    public function __construct(protected array $data)
    {
        //
    }

    /**
     * Получить слот по имени.
     *
     * @param string $name
     * @param array|null $default
     * @return Slot|null
     */
    public function slot(string $name, ?array $default = null): ?Slot
    {
        if (isset($this->data['slots'][$name])) {
            return new Slot($this->data['slots'][$name]);
        } else {
            return Container::getInstance()->call($default);
        }
    }
}