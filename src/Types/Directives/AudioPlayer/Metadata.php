<?php

namespace Alice\Types\Directives\AudioPlayer;

/**
 * Метаданные для AudioPlayer (title, sub_title, art, background_image).
 */
class Metadata
{
    /** @var array<string,mixed> */
    protected array $meta = [
        'title' => null,
        'sub_title' => null,
        'art' => [
            'url' => null,
        ],
        'background_image' => [
            'url' => null,
        ],
    ];

    /**
     * @param string|null $artist Имя артиста.
     * @param string|null $title Название композиции
     * @param string|null $cover URL обложки альбома
     * @param string|null $background URL фонового изображения
     */
    public function __construct(?string $artist = null, ?string $title = null, ?string $cover = null, ?string $background = null)
    {
        $this->meta = [
            'title' => $title,
            'sub_title' => $artist,
        ];

        $this->meta['art'] = [
            'url' => $cover,
        ];

        $this->meta['background_image'] = [
            'url' => $background,
        ];
    }

    /**
     * Преобразовать метаданные в массив.
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return $this->meta;
    }
}