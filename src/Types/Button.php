<?php

declare(strict_types=1);

namespace Alice\Types;

use Alice\Support\Render;

/**
 * Модель кнопки с рендерингом заголовка и удобными сеттерами.
 */
class Button
{
    protected string $title;

    /**
     * @param string|array $title Заголовок кнопки или варианты
     * @param string|null $action Имя действия
     * @param array $payload Нагрузочный payload
     * @param string|null $url Ссылка (если есть)
     * @param bool $hide Скрывать ли кнопку на экране
     */
    public function __construct(
        string|array $title,
        protected ?string $action = null,
        protected array $payload = [],
        protected ?string $url = null,
        protected bool $hide = true
    ) {
        $rawTitle = is_array($title) ? Render::variant($title) : $title;

        $rendered = Render::process([
            'text' => $rawTitle,
            'tts'  => ''
        ]);

        $this->title = $rendered['text'];
    }

    /**
     * Задает действие кнопки.
     *
     * @param string $action Имя действия
     * @return static
     */
    public function action(string $action): static
    {
        $this->action = $action;
        return $this;
    }

    /**
     * Устанавливает URL для кнопки.
     *
     * @param string|null $url Ссылка
     * @return static
     */
    public function url(?string $url = null): static
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Устанавливает payload для кнопки.
     *
     * @param array $payload Нагрузочный payload
     * @return static
     */
    public function payload(array $payload): static
    {
        $this->payload = $payload;
        $this->hide = true;
        return $this;
    }

    /**
     * Устанавливает флаг видимости кнопки на экране.
     *
     * @param bool $hide Скрывать ли кнопку
     * @return static
     */
    public function hideable(bool $hide = true): static
    {
        $this->hide = $hide;
        return $this;
    }

    /**
     * Преобразует кнопку в массив для ответа платформы.
     *
     * @return array
     */
    public function toArray(): array
    {
        $button = [
            'title' => $this->title,
            'hide' => $this->hide,
        ];

        if ($this->url) {
            $button['url'] = $this->url;
        }

        $payload = $this->payload;

        if ($this->action) {
            $payload['$action'] = $this->action;
        }

        if (!empty($payload)) {
            $button['payload'] = $payload;
        }

        return $button;
    }
}
