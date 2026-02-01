<?php

namespace Alice\Types\Directives;

/**
 * Базовый абстрактный Directive для директив ответа.
 */
abstract class Directive
{
    protected array $directive = [];

    /**
     * Преобразовать директиву в массив.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->directive;
    }
}