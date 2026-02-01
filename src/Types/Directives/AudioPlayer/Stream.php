<?php

namespace Alice\Types\Directives\AudioPlayer;

/**
 * Представление потока аудио (Stream) для AudioPlayer.
 */
class Stream
{
    /** @var array<string,mixed> */
    protected array $stream = [
        'url' => null,
        'offset_ms' => 0,
        'token' => null,
    ];

    /**
     * @param string $url
     * @param int $offsetMs
     * @param string|null $token
     */
    public function __construct(string $url, int $offsetMs = 0, ?string $token = null) {
        $this->stream = [
            'url' => $url,
            'offset_ms' => $offsetMs,
            'token' => $token ?? md5($url),
        ];
    }

    /**
     * Преобразовать Stream в массив.
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return $this->stream;
    }
}