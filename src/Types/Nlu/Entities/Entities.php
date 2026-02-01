<?php

namespace Alice\Types\Nlu\Entities;

use Alice\Support\Container;

/**
 * Коллекция NLU-сущностей, оборачивает сырые данные в конкретные классы Entity.
 */
class Entities
{
    /**
     * @param array $entities
     */
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

    /**
     * Получить сущности по типу.
     *
     * @param string $type
     * @param mixed $default
     * @return array
     */
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

    /**
     * Количество сущностей.
     * @return int
     */
    public function count(): int
    {
        return count($this->entities);
    }

    /**
     * Вернуть все сущности.
     * @return array
     */
    public function all(): array
    {
        return $this->toArray();
    }

    /**
     * Преобразовать в массив.
     * @return array
     */
    public function toArray(): array
    {
        return $this->entities;
    }
}