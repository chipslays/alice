<?php

namespace Alice\Types\Directives;

abstract class Directive
{
    protected array $directive = [];

    public function toArray(): array
    {
        return $this->directive;
    }
}