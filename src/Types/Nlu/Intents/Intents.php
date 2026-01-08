<?php

namespace Alice\Types\Nlu\Intents;

use Alice\Support\Container;

class Intents
{
    public function __construct(protected array $intents = [])
    {
        //
    }

    public function get(string $name, mixed $default = null): ?Intent
    {
        if (isset($this->intents[$name])) {
            return new Intent($this->intents[$name]);
        } else {
            return Container::getInstance()->call($default);
        }
    }

    public function count(): int
    {
        return count($this->intents);
    }

    public function all(): array
    {
        return $this->toArray();
    }

    public function toArray(): array
    {
        return $this->intents;
    }
}