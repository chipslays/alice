<?php

namespace Alice\Types\Directives;

/**
 * Базовый абстрактный Directive для директив ответа.
 */
abstract class Directive
{
    /** @var array<string,mixed> */
    protected array $directive = [];

    /**
     * Преобразовать директиву в массив.
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return $this->directive;
    }
}