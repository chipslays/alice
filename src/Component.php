<?php

namespace Alice;

use Alice\Support\Collection;

class Component
{
    protected readonly Collection $data;

    public function __construct(array $data = [])
    {
        $this->data = new Collection($data);
    }

    public function register(Alice $alice, Context $context, Settings $settings): void
    {
        //
    }
}
