<?php

namespace Alice;

use Alice\Support\Collection;

/**
 * Настройки приложения (skill_id, oauth_token, storage_path и т.д.).
 *
 * Расширяет Collection для удобного доступа.
 */
class Settings extends Collection
{
    protected $default = [
        'skill_id' => null,
        'oauth_token' => null,
        'storage_path' => null,
        'assets' => [],
        'buttons' => [],
    ];

    /**
     * Инициализация настроек с применением значений по умолчанию.
     *
     * @param array $items Пользовательские настройки
     * @return void
     */
    public function construct(array $items = []): void
    {
        parent::__construct(array_replace_recursive($this->default, $items));
    }
}
