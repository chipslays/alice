<?php

namespace Alice\Types\Nlu\Entities;

use Alice\Support\Collection;
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
     * Возвращает массив найденных сущностей по типу.
     *
     * Можно передать как строку или как класс сущности.
     *
     * @param string $type
     * @param array $default
     * @return Collection
     */
    public function get(string $type, array $default = []): Collection
    {
        $result = [];

        foreach ($this->entities as $entity) {
            if ($entity->type === $type || $entity instanceof $type) {
                $result[] = $entity;
            }
        }

        if (empty($result)) {
            return new Collection($default);
        }

        return new Collection($result);
    }

    /**
     * Получить первую сущность по типу.
     *
     * @param string $type
     * @param Entity|null $default
     * @return Entity|null
     */
    public function first(string $type, ?Entity $default = null): ?Entity
    {
        return $this->get($type)->first(default: $default);
    }

    /**
     * Получить последнюю сущность по типу.
     *
     * @param string $type
     * @param Entity|null $default
     * @return Entity|null
     */
    public function last(string $type, ?Entity $default = null): ?Entity
    {
        return $this->get($type)->last(default: $default);
    }

    public function filter(callable $callback): static
    {
        return new static(array_filter($this->entities, $callback));
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
