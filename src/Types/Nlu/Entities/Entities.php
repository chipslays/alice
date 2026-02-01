<?php

namespace Alice\Types\Nlu\Entities;

use Alice\Support\Container;

/**
 * Коллекция NLU-сущностей, оборачивает сырые данные в конкретные классы Entity.
 */
class Entities
{
    /** @var array<int,Entity> */
    protected array $entities = [];

    /**
     * @param array<int,mixed> $entities Сырые данные сущностей
     */
    public function __construct(array $entities = [])
    {
        foreach ($entities as $key => $entity) {
            /** @var array<string,mixed> $entityData */
            $entityData = is_array($entity) ? $entity : [];
            $type = is_scalar($entityData['type'] ?? null) ? (string) $entityData['type'] : '';

            $entities[$key] = match ($type) {
                'YANDEX.FIO' => new FioEntity($entityData),
                'YANDEX.GEO' => new GeoEntity($entityData),
                'YANDEX.NUMBER' => new NumberEntity($entityData),
                'YANDEX.DATETIME' => new DatetimeEntity($entityData),
                default => new Entity($entityData),
            };
        }

        // Сохраняем обёрнутые сущности в поле объекта
        $this->entities = $entities;
    }

    /**
     * Получить сущности по типу.
     *
     * @param string $type
     * @param mixed $default
     * @return array<int,Entity>
     */
    public function get(string $type, mixed $default = []): array
    {
        $result = [];

        foreach ($this->entities as $entity) {
            if ($entity->type === $type || $entity instanceof $type) {
                $result[] = $entity;
            }
        }

        if (empty($result)) {
            if ($default === null || $default === []) {
                return [];
            }

            if (is_callable($default) || is_string($default) || is_array($default)) {
                $fallback = (array) Container::getInstance()->call($default);
                $filtered = [];
                foreach ($fallback as $item) {
                    if ($item instanceof Entity) {
                        $filtered[] = $item;
                    }
                }
                return $filtered;
            }

            return [];
        }

        return $result;
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
     * @return array<int, Entity>
     */
    public function all(): array
    {
        return $this->entities;
    }

    /**
     * Преобразовать в массив.
     * @return array<int,array<string,mixed>>
     */
    public function toArray(): array
    {
        return array_map(fn(Entity $e) => $e->toArray(), $this->entities);
    }
}