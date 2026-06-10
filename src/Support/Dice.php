<?php

declare(strict_types=1);

namespace Alice\Support;

class Dice
{
    /**
     * Проверяет удачу на основе заданного процента.
     *
     * Пример: Dice::roll(25.5) — вернет true в 25.5% случаев.
     *
     * @param float $chance Шанс успеха от 0 до 100.
     * @return bool
     */
    public static function roll(float $chance): bool
    {
        if ($chance <= 0) {
            return false;
        }

        if ($chance >= 100) {
            return true;
        }

        // Умножаем на 100, чтобы поддерживать сотые доли процента (например, 0.01%)
        // Генерируем число от 1 до 10000
        return random_int(1, 10000) <= (int) ($chance * 100);
    }

    /**
     * Выбирает один элемент из массива с учетом веса (вероятности).
     *
     * Пример: Dice::pick(['rare' => 10, 'common' => 90])
     * 'common' будет выпадать в 9 раз чаще.
     *
     * @param array<mixed, int|float> $items Массив [значение => вес]
     * @return mixed Ключ массива, на который пал выбор
     */
    public static function pick(array $items): mixed
    {
        if (empty($items)) {
            return null;
        }

        $totalWeight = array_sum($items);

        // Если веса некорректны или равны 0, берем случайный ключ
        if ($totalWeight <= 0) {
            return array_rand($items);
        }

        // Генерируем случайное число в диапазоне от 1 до Суммы весов
        // Используем точность до сотых для float весов
        $random = random_int(1, (int) ($totalWeight * 100));
        $current = 0;

        foreach ($items as $key => $weight) {
            $current += (int) ($weight * 100);
            if ($random <= $current) {
                return $key;
            }
        }

        // На случай погрешностей округления возвращаем последний ключ
        return array_key_last($items);
    }

    /**
     * Подбросить монетку (50/50).
     */
    public static function flip(): bool
    {
        return (bool) random_int(0, 1);
    }
}
