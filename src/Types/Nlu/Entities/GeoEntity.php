<?php

namespace Alice\Types\Nlu\Entities;

class GeoEntity extends Entity
{
    public function country(mixed $default = null): ?string
    {
        return $this->value('country', $default);
    }

    public function city(mixed $default = null): ?string
    {
        return $this->data['value']['city'] ?? null;
        return $this->value('city', $default);
    }

    public function street(mixed $default = null): ?string
    {
        return $this->value('street', $default);
    }

    public function houseNumber(mixed $default = null): ?string
    {
        return $this->value('house_number', $default);
    }

    public function airport(mixed $default = null): ?string
    {
        return $this->value('airport', $default);
    }

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