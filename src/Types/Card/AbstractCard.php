<?php

namespace Alice\Types\Card;

use Alice\Support\Buttons;

abstract class AbstractCard
{
    protected array $card = [];

    public function toArray(): array
    {
        return $this->card;
    }

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