<?php

namespace Alice\Types\Nlu\Entities;

class NumberEntity extends Entity
{
    public function toNumber(): int
    {
        return (int) $this->value();
    }

    public function toFloat(int $percision = 2, int $mode = PHP_ROUND_HALF_UP): float
    {
        return (float) round($this->value(), $percision, $mode);
    }
}