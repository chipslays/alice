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
        return $this->value('country', $default);
    }

    /**
     * Получить город из сущности.
     * @param mixed $default Значение по умолчанию
     * @return string|null
     */
    public function city(mixed $default = null): ?string
    {
        return $this->value('city', $default);
    }

    /**
     * Получить улицу из сущности.
     * @param mixed $default Значение по умолчанию
     * @return string|null
     */
    public function street(mixed $default = null): ?string
    {
        return $this->value('street', $default);
    }

    /**
     * Получить номер дома из сущности.
     * @param mixed $default Значение по умолчанию
     * @return string|null
     */
    public function houseNumber(mixed $default = null): ?string
    {
        return $this->value('house_number', $default);
    }

    /**
     * Получить аэропорт из сущности.
     * @param mixed $default Значение по умолчанию
     * @return string|null
     */
    public function airport(mixed $default = null): ?string
    {
        return $this->value('airport', $default);
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