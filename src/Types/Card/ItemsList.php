<?php

namespace Alice\Types\Card;

use Alice\Support\Assets;

/**
 * @see https://yandex.ru/dev/dialogs/alice/doc/ru/response-card-itemslist
 * @see https://yandex.ru/dev/dialogs/alice/doc/ru/interface#list
 */
class ItemsList extends AbstractCard
{
    /** @var array<string,mixed> */
    protected array $card = [
        'type' => 'ItemsList',
        'items' => [],
    ];

    /**
     * Установить заголовок списка.
     * @param string $text
     * @return static
     */
    public function header(string $text): static
    {
        if (!isset($this->card['header']) || !is_array($this->card['header'])) {
            $this->card['header'] = [];
        }
        $this->card['header']['text'] = $text;

        return $this;
    }

    /**
     * Добавить элемент в список.
     * @param string $imageId
     * @param string|null $title
     * @param string|null $description
     * @param Button|string|null $button
     * @return static
     */
    public function add(string $imageId, ?string $title = null, ?string $description = null, Button|string|null $button = null): static
    {
        $variant = Assets::get($imageId, $imageId);
        if (!is_scalar($variant)) {
            $variant = $imageId;
        }

        if (!isset($this->card['items']) || !is_array($this->card['items'])) {
            $this->card['items'] = [];
        }

        $this->card['items'][] = [
            'image_id' => (string) $variant,
            'title' => $title,
            'description' => $description,
            'button' => $this->resolveButton($button),
        ];

        return $this;
    }

    /**
     * Установить футер списка.
     * @param string $text
     * @param Button|string|null $button
     * @return static
     */
    public function footer(string $text, Button|string|null $button = null): static
    {
        if (!isset($this->card['footer']) || !is_array($this->card['footer'])) {
            $this->card['footer'] = [];
        }
        $this->card['footer']['text'] = $text;
        $this->card['footer']['button'] = $this->resolveButton($button);

        return $this;
    }
}