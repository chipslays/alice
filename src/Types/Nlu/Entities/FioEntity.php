<?php

namespace Alice\Types\Nlu\Entities;

class FioEntity extends Entity
{
    public function firstName(mixed $default = null): ?string
    {
        return $this->value('first_name', $default);
    }

    public function middleName(mixed $default = null): ?string
    {
        return $this->value('patronymic_name', $default);
    }

    public function lastName(mixed $default = null): ?string
    {
        return $this->value('last_name', $default);
    }

    public function fullName(): string
    {
        return implode(' ', array_filter([
            $this->firstName(),
            $this->middleName(),
            $this->lastName()])
        );
    }
}