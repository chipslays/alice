<?php

namespace Alice\Types\Nlu\Intents;

use Alice\Support\Container;

/**
 * Класс одного интента (намерения) с доступом к слотам.
 */
class Intent
{
    /**
     * @param array<string,mixed> $data
     */
    public function __construct(protected array $data)
    {
        //
    }

    /**
     * Получить слот по имени.
     *
     * @param string $name
     * @param mixed $default Значение по умолчанию или callable
     * @return Slot|null
     */
    public function slot(string $name, mixed $default = null): ?Slot
    {
        /** @var array<string,mixed>|null $slots */
        $slots = $this->data['slots'] ?? null;
        $slots = is_array($slots) ? $slots : null;

        if ($slots !== null && isset($slots[$name]) && is_array($slots[$name])) {
            /** @var array<string,mixed> $slotData */
            $slotData = $slots[$name];
            return new Slot($slotData);
        }

        if ($default === null) {
            return null;
        }

        if (is_callable($default) || is_string($default) || is_array($default)) {
            $res = Container::getInstance()->call($default);
            return $res instanceof Slot ? $res : null;
        }

        return null;
    }


}