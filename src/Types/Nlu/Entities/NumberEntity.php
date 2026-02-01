<?php

namespace Alice\Types\Nlu\Entities;

/**
 * Сущность числа (YANDEX.NUMBER) с преобразованием к int/float.
 */
class NumberEntity extends Entity
{
    /**
     * Получить значение как целое число.
     *
     * @return int
     */
    public function toNumber(): int
    {
        return (int) $this->value();
    }

    /**
     * Получить значение как float с округлением.
     *
     * @param int $percision Количество десятичных знаков (точность)
     * @param int $mode Режим округления (например, PHP_ROUND_HALF_UP)
     * @return float
     */
    public function toFloat(int $percision = 2, int $mode = PHP_ROUND_HALF_UP): float
    {
        return (float) round($this->value(), $percision, $mode);
    }
}