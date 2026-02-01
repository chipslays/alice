<?php

namespace Alice\Types\Card;

use Alice\Support\Buttons;

/**
 * Базовый класс карточки для ответов (карточки наследуют и формируют массив данных).
 */
abstract class AbstractCard
{
    protected array $card = [];

    /**
     * Преобразует карточку в массив для ответа.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->card;
    }

    /**
     * Нормализует параметр кнопки в массив.
     *
     * @param Button|string|null $button
     * @return array
     */
    protected function resolveButton(Button|string|null $button = null): array
    {
        if ($button === null) {
            $button = [];
        } else if (is_string($button)) {
            /**
             * Buttons::add('foo', new Button('bar', action: 'baz'));
             */
            $button = Buttons::get($button);
            $button = $button instanceof Button ? $button->toArray() : [];
        }

        return $button;
    }
}