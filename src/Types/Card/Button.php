<?php

namespace Alice\Types\Card;

use Alice\Support\Render;

class Button
{
    protected string $text;

    /**
     * Конструктор кнопки карточки.
     *
     * @param string|array<int,string> $text Текст кнопки или варианты
     * @param string|null $action Действие
     * @param array<string,mixed> $payload Нагрузочный payload
     * @param string|null $url Ссылка
     */


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

    /**
     * Установить действие кнопки.
     * @param string $action
     * @return static
     */
    public function action(string $action): static
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Установить URL для кнопки.
     * @param string|null $url
     * @return static
     */
    public function url(?string $url = null): static
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Установить payload для кнопки.
     * @param array<string,mixed> $payload
     * @return static
     */
    public function payload(array $payload): static
    {
        $this->payload = $payload;

        return $this;
    }

    /**
     * Преобразовать кнопку в массив.
     * @return array<string,mixed>
     */
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