<?php

namespace Alice\Types\Directives\AudioPlayer;

use Alice\Types\Directives\Directive;

/**
 * Directive для управления AudioPlayer (Play/Stop) и автозапуска.
 */
class AudioPlayer extends Directive
{
    protected array $directive = [
        'action' => null,
    ];

    public protected(set) bool $autoplay = false;

    /**
     * Включить воспроизведение стрима.
     *
     * @param Stream|string $stream URL или Stream
     * @param Metadata|null $meta Доп. метаданные (текст, арт)
     * @param bool $autoplay Автозапуск
     * @return static
     */
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

    /**
     * Остановить воспроизведение.
     *
     * @return static
     */
    public function stop(): static
    {
        $this->directive['action'] = 'Stop';

        return $this;
    }
}