<?php

namespace Alice\Types\Nlu\Entities;

use Alice\Support\Container;
use Closure;

/**
 * Базовый класс сущности NLU (например, YANDEX.NUMBER, YANDEX.GEO и др.).
 */
class Entity
{
    public readonly ?string $type;

    public readonly ?string $start;

    public readonly ?string $end;

    /**
     * @param array<string,mixed> $data Данные сущности
     */
    public function __construct(protected array $data)
    {
        if (isset($data['type']) && is_scalar($data['type'])) {
            $this->type = (string) $data['type'];
        } else {
            $this->type = null;
        }

        $tokens = is_array($data['tokens'] ?? null) ? $data['tokens'] : [];
        $this->start = is_scalar($tokens['start'] ?? null) ? (string) $tokens['start'] : null;
        $this->end = is_scalar($tokens['end'] ?? null) ? (string) $tokens['end'] : null;
    }

    /**
     * Получить значение сущности или конкретного ключа.
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function value(?string $key = null, mixed $default = null): mixed
    {
        $val = $this->data['value'] ?? null;

        // Например, `YANDEX.NUMBER` имеет в поле `value` число, а не массив.
        if (!is_array($val)) {
            if ($key === null) {
                if ($default instanceof Closure) {
                    return Container::getInstance()->call($default);
                }
                return $val ?? $default;
            }
            return $val ?? $default;
        }

        if ($key === null) {
            return $val;
        }

        if (array_key_exists($key, $val)) {
            return $val[$key];
        }

        if ($default instanceof Closure) {
            return Container::getInstance()->call($default);
        }

        return $default;
    }

    /**
     * Преобразовать сущность в массив.
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
