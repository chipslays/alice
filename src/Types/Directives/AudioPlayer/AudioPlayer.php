<?php

namespace Alice\Types\Directives\AudioPlayer;

use Alice\Types\Directives\Directive;

class AudioPlayer extends Directive
{
    protected array $directive = [
        'action' => null,
    ];

    public protected(set) bool $autoplay = false;

    public function play(Stream|string $stream, ?Metadata $meta = null, bool $autoplay = false): static
    {
        $this->autoplay = $autoplay;

        $this->directive['action'] = 'Play';

        $this->directive['item'] = [
            'stream' => $stream instanceof Stream ? $stream->toArray() : (new Stream($stream))->toArray(),
            'metadata' => $meta?->toArray(),
        ];

        return $this;
    }

    public function stop(): static
    {
        $this->directive['action'] = 'Stop';

        return $this;
    }
}