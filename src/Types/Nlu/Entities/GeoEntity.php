<?php

namespace Alice\Types\Nlu\Entities;

/**
 * Гео-сущность (YANDEX.GEO) с методами доступа к частям адреса.
 */
class GeoEntity extends Entity
{
    /**
     * Получить страну из сущности.
     * @param mixed $default Значение по умолчанию
     * @return string|null
     */
    public function country(mixed $default = null): ?string
    {
        $val = $this->value('country', $default);
        return is_scalar($val) ? (string) $val : (is_null($val) ? null : null);
    }

    /**
     * Получить город из сущности.
     * @param mixed $default Значение по умолчанию
     * @return string|null
     */
    public function city(mixed $default = null): ?string
    {
        $val = $this->value('city', $default);
        return is_scalar($val) ? (string) $val : (is_null($val) ? null : null);
    }

    /**
     * Получить улицу из сущности.
     * @param mixed $default Значение по умолчанию
     * @return string|null
     */
    public function street(mixed $default = null): ?string
    {
        $val = $this->value('street', $default);
        return is_scalar($val) ? (string) $val : (is_null($val) ? null : null);
    }

    /**
     * Получить номер дома из сущности.
     * @param mixed $default Значение по умолчанию
     * @return string|null
     */
    public function houseNumber(mixed $default = null): ?string
    {
        $val = $this->value('house_number', $default);
        return is_scalar($val) ? (string) $val : (is_null($val) ? null : null);
    }

    /**
     * Получить аэропорт из сущности.
     * @param mixed $default Значение по умолчанию
     * @return string|null
     */
    public function airport(mixed $default = null): ?string
    {
        $val = $this->value('airport', $default);
        return is_scalar($val) ? (string) $val : (is_null($val) ? null : null);
    }

    /**
     * Получить полный адрес в виде строки.
     * @return string|null
     */
    public function fullAddress(): ?string
    {
        $addressParts = [];

        if ($country = $this->country()) {
            $addressParts[] = $country;
        }

        if ($city = $this->city()) {
            $addressParts[] = $city;
        }

        if ($street = $this->street()) {
            $addressParts[] = $street;
        }

        if ($houseNumber = $this->houseNumber()) {
            $addressParts[] = $houseNumber;
        }

        if (empty($addressParts)) {
            return null;
        }

        return implode(', ', $addressParts);
    }
}