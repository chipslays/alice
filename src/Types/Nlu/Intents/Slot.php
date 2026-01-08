<?php

namespace Alice\Types\Nlu\Intents;

use Alice\Support\Container;

class Slot
{
    public protected(set) string $type;

    public protected(set) ?string $start = null;

    public protected(set) ?string $end = null;

    public function __construct(protected array $data)
    {
        $this->type = $data['type'];
        $this->end = $data['value'];

        if (isset($data['tokens'])) {
            $this->start = $data['tokens']['start'];
            $this->end = $data['tokens']['end'];
        }
    }

    public function value(?string $key = null, mixed $default = null): mixed
    {
        if (!is_array($this->data['value'])) {
            return $this->data['value'];
        }

        return $this->data['value'][$key] ?? Container::getInstance()->call($default);
    }
}