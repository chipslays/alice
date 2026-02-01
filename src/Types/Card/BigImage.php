<?php

namespace Alice\Types\Card;

use Alice\Support\Assets;

/**
 * @see https://yandex.ru/dev/dialogs/alice/doc/ru/response-card-bigimage
 * @see https://yandex.ru/dev/dialogs/alice/doc/ru/interface#card
 */
class BigImage extends AbstractCard
{
    /** @var array<string,mixed> */
    protected array $card = [
        'type' => 'BigImage',
        'image_id' => null,
        'title' => null,
        'description' => null,
        'button' => [],
    ];

    /**
     * @param string $imageId
     * @param string|null $title
     * @param string|null $description
     * @param Button|null $button
     */
    public function __construct(string $imageId, ?string $title = null, ?string $description = null, ?Button $button = null)
    {
        if ($button) {
            $this->button($button);
        }

        $this
            ->image($imageId)
            ->title($title)
            ->description($description);
    }

    /**
     * Установить изображение.
     * @param string $imageId
     * @return static
     */
    public function image(string $imageId): static
    {
        $this->card['image_id'] = Assets::get($imageId) ?? $imageId;

        return $this;
    }

    /**
     * Установить заголовок карточки.
     * @param string|null $title
     * @return static
     */
    public function title(?string $title = null): static
    {
        $this->card['title'] = $title;

        return $this;
    }

    /**
     * Установить описание карточки.
     * @param string|null $description
     * @return static
     */
    public function description(?string $description = null): static
    {
        $this->card['description'] = $description;

        return $this;
    }

    /**
     * Установить кнопку карточки.
     * @param Button $button
     * @return static
     */
    public function button(Button $button): static
    {
        $this->card['button'] = $button->toArray();

        return $this;
    }
}