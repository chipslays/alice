<?php

namespace Alice;

use Alice\Support\Collection;

class Settings extends Collection
{
    protected $default = [
        'skill_id' => null,
        'oauth_token' => null,
        'storage_path' => null,
        'assets' => [],
        'buttons' => [],
    ];

    public function construct(array $items = []): void
    {
        parent::__construct(array_replace_recursive($this->default, $items));
    }
}
