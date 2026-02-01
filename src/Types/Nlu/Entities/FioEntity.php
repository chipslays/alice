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
        $val = $this->value('first_name', $default);
        return is_scalar($val) ? (string) $val : (is_null($val) ? null : null);
    }

    /**
     * Получить отчество.
     *
     * @param mixed $default
     * @return string|null
     */
    public function middleName(mixed $default = null): ?string
    {
        $val = $this->value('patronymic_name', $default);
        return is_scalar($val) ? (string) $val : (is_null($val) ? null : null);
    }

    /**
     * Получить фамилию.
     *
     * @param mixed $default
     * @return string|null
     */
    public function lastName(mixed $default = null): ?string
    {
        $val = $this->value('last_name', $default);
        return is_scalar($val) ? (string) $val : (is_null($val) ? null : null);
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