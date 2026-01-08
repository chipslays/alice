<?php

namespace Alice\Types\Nlu\Entities;

use Alice\Support\Container;

class Entities
{
    public function __construct(protected array $entities = [])
    {
        foreach ($entities as $key => $entity) {
            $entities[$key] = match ($entity['type']) {
                'YANDEX.FIO' => new FioEntity($entity),
                'YANDEX.GEO' => new GeoEntity($entity),
                'YANDEX.NUMBER' => new NumberEntity($entity),
                'YANDEX.DATETIME' => new DatetimeEntity($entity),
                default => new Entity($entity),
            };
        }
    }

    public function get(string $type, mixed $default = []): array
    {
        $result = [];

        foreach ($this->entities as $entity) {
            if ($entity->type === $type || $entity instanceof $type) {
                $result[] = $entity;
            }
        }

        return empty($result) ? Container::getInstance()->call($default) : $result;
    }

    public function count(): int
    {
        return count($this->entities);
    }

    public function all(): array
    {
        return $this->toArray();
    }

    public function toArray(): array
    {
        return $this->entities;
    }
}