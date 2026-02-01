<?php

namespace Alice;

use Alice\Support\Collection;

/**
 * Настройки приложения (skill_id, oauth_token, storage_path и т.д.).
 *
 * Расширяет Collection для удобного доступа.
 *
 * @extends Collection<mixed>
 */
class Settings extends Collection
{
    /** @var array<string,mixed> */
    protected array $default = [
        'skill_id' => null,
        'oauth_token' => null,
        'storage_path' => null,
        'assets' => [],
        'buttons' => [],
    ];

    /**
     * Инициализация настроек с применением значений по умолчанию.
     *
     * @param array<string,mixed> $items Пользовательские настройки
     */
    public function __construct(array $items = [])
    {
        parent::__construct(array_replace_recursive($this->default, $items));
    }
}
