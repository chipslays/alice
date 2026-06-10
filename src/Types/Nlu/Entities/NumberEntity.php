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
        $val = $this->value();
        if (!is_numeric($val)) {
            return 0;
        }
        return (int) ((float) $val);
    }

    /**
     * Получить значение как float с округлением.
     *
     * @param int $percision Количество десятичных знаков (точность)
     * @param 1|2|3|4 $mode Режим округления (например, PHP_ROUND_HALF_UP)
     * @return float
     */
    public function toFloat(int $percision = 2, int $mode = PHP_ROUND_HALF_UP): float
    {
        $val = $this->value();
        if (!is_numeric($val)) {
            return 0.0;
        }
        return (float) round((float) $val, $percision, $mode);
    }
}