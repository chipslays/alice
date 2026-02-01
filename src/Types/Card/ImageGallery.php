<?php

namespace Alice\Types\Card;

use Alice\Support\Assets;

/**
 * @see https://yandex.ru/dev/dialogs/alice/doc/ru/response-card-imagegallery
 * @see https://yandex.ru/dev/dialogs/alice/doc/ru/interface#images-list
 */
class ImageGallery extends AbstractCard
{
    protected array $card = [
        'type' => 'ImageGallery',
        'items' => [],
    ];

    /**
     * Добавляет изображение в галерею.
     *
     * @param string $imageId Идентификатор изображения
     * @param string $title Заголовок изображения
     * @param Button|string|null $button Кнопка или её представление
     * @return static
     */
    public function add(string $imageId, string $title, Button|string|null $button = null): static
    {
        $this->card['items'][] = [
            'image_id' => Assets::get($imageId, $imageId),
            'title' => $title,
            'button' => $this->resolveButton($button),
        ];

        return $this;
    }
}