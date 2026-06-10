<?php

namespace Alice\Types\Card;

use Alice\Support\Buttons;

/**
 * Базовый класс карточки для ответов (карточки наследуют и формируют массив данных).
 */
abstract class AbstractCard
{
    /** @var array<string,mixed> */
    protected array $card = [];

    /**
     * Преобразует карточку в массив для ответа.
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return $this->card;
    }

    /**
     * Нормализует параметр кнопки в массив.
     *
     * @param Button|array<int|string,mixed>|string|null $button
     * @return array<int|string,mixed>
     */
    protected function resolveButton(Button|array|string|null $button = null): array
    {
        if ($button === null) {
            return [];
        }

        if ($button instanceof Button) {
            return $button->toArray();
        }

        if (is_string($button)) {
            $value = Buttons::get($button, []);
            if ($value instanceof Button) {
                return $value->toArray();
            }
            return is_array($value) ? $value : (array) $value;
        }

        return (array) $button;
    }
}