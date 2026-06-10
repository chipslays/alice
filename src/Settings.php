<?php

namespace Alice;

use Alice\Support\Collection;
use Alice\Types\Button;

/**
 * Настройки приложения (skill_id, oauth_token и т.д.).
 *
 * @extends Collection<mixed>
 */
class Settings extends Collection
{
    /** @var array<string,mixed> */
    protected array $default = [];

    /**
     * Инициализация настроек с применением значений по умолчанию.
     *
     * @param array<string,mixed> $items Пользовательские настройки
     */
    public function __construct(array $items = [])
    {
        $this->default = [
            'skill_id' => null, // Идентификатор навыка, если не указан, берется из запроса
            'oauth_token' => null, // для отправки запросов к Яндекс API
            'storage' => [
                'path' => null, // Полный путь до папки хранения данных
            ],
            'scenes' => [
                'state' => 'session', // session|user|application
            ],
            'assets' => [
                'win-1' => 'alice-sounds-game-win-1.opus',
                'win-2' => 'alice-sounds-game-win-2.opus',
                'win-3' => 'alice-sounds-game-win-3.opus',
                // ...
            ],
            'buttons' => [
                'confirm' => [
                    Button::make('Да', 'YES'),
                    Button::make('Нет', 'NO'),
                ],
                // ...
            ],
            'middlewares' => [
                // Глобальные мидлвари, которые будут применяться
                // ко всем сценам и событиям
                'global' => [],

                // Алиасы для удобного применения мидлварей
                'aliases' => [],
            ],
        ];

        parent::__construct(array_replace_recursive($this->default, $items));
    }
}
