<?php

namespace Alice\Types\Directives\AudioPlayer;

class Stream
{
    protected array $stream = [
        'url' => null,
        'offset_ms' => 0,
        'token' => null,
    ];

    public function __construct(string $url, int $offsetMs = 0, ?string $token = null) {
        $this->stream = [
            'url' => $url,
            'offset_ms' => $offsetMs,
            'token' => $token ?? md5($url),
        ];
    }

    public function toArray(): array
    {
        return $this->stream;
    }
}