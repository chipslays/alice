<?php

namespace Alice\Types\Card;

use Alice\Support\Assets;

/**
 * @see https://yandex.ru/dev/dialogs/alice/doc/ru/response-card-itemslist
 * @see https://yandex.ru/dev/dialogs/alice/doc/ru/interface#list
 */
class ItemsList extends AbstractCard
{
    protected array $card = [
        'type' => 'ItemsList',
        'items' => [],
    ];

    public function header(string $text): static
    {
        $this->card['header']['text'] = $text;

        return $this;
    }

    public function add(string $imageId, ?string $title = null, ?string $description = null, Button|string|null $button = null): static
    {
        $this->card['items'][] = [
            'image_id' => Assets::get($imageId, $imageId),
            'title' => $title,
            'description' => $description,
            'button' => $this->resolveButton($button),
        ];

        return $this;
    }

    public function footer(string $text, Button|string|null $button = null): static
    {
        $this->card['footer']['text'] = $text;
        $this->card['footer']['button'] = $this->resolveButton($button);

        return $this;
    }
}