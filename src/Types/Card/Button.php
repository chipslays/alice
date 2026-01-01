<?php

namespace Alice\Types\Card;

use Alice\Support\Render;

class Button
{
    protected string $text;

    public function __construct(
        string|array $text,
        protected ?string $action = null,
        protected array $payload = [],
        protected ?string $url = null,
    ) {
        $rawText = is_array($text) ? Render::variant($text) : $text;

        $rendered = Render::process([
            'text' => $rawText,
            'tts'  => ''
        ]);

        $this->text = $rendered['text'];
    }

    public function action(string $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function url(?string $url = null): static
    {
        $this->url = $url;

        return $this;
    }

    public function payload(array $payload): static
    {
        $this->payload = $payload;

        return $this;
    }

    public function toArray(): array
    {
        $button = [
            'text' => $this->text,
        ];

        if ($this->url) {
            $button['url'] = $this->url;
        }

        $payload = $this->payload;

        if ($this->action) {
            $payload['__action__'] = $this->action;
        }

        if ($payload) {
            $button['payload'] = $payload;
        }

        return $button;
    }
}