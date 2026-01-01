<?php

namespace Alice;

use Alice\Support\Collection;

class Settings extends Collection
{
    protected $default = [
        'storage_path' => null,
        'assets' => [],
        'buttons' => [],
    ];

    public function construct(array $items = []): void
    {
        parent::__construct(array_replace_recursive($this->default, $items));
    }
}
