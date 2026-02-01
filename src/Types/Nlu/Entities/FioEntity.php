<?php

namespace Alice\Types\Nlu\Entities;

/**
 * FIO-сущность (YANDEX.FIO) с методами доступа к частям ФИО.
 */
class FioEntity extends Entity
{
    /**
     * Получить имя.
     *
     * @param mixed $default
     * @return string|null
     */
    public function firstName(mixed $default = null): ?string
    {
        return $this->value('first_name', $default);
    }

    /**
     * Получить отчество.
     *
     * @param mixed $default
     * @return string|null
     */
    public function middleName(mixed $default = null): ?string
    {
        return $this->value('patronymic_name', $default);
    }

    /**
     * Получить фамилию.
     *
     * @param mixed $default
     * @return string|null
     */
    public function lastName(mixed $default = null): ?string
    {
        return $this->value('last_name', $default);
    }

    /**
     * Получить полное ФИО (имя, отчество, фамилия).
     *
     * @return string
     */
    public function fullName(): string
    {
        return implode(' ', array_filter([
            $this->firstName(),
            $this->middleName(),
            $this->lastName()])
        );
    }
}