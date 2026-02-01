<?php

namespace Alice\Types\Nlu\Intents;

use Alice\Support\Container;

class Slot
{
    public string $type;

    public ?string $start = null;

    public ?string $end = null;

    /**
     * Конструктор слота интента.
     *
     * @param array<string,mixed> $data Данные слота
     */
    public function __construct(protected array $data)
    {
        $this->type = is_scalar($data['type'] ?? null) ? (string) $data['type'] : '';

        $val = $data['value'] ?? null;
        $this->end = is_scalar($val) ? (string) $val : null;

        if (isset($data['tokens']) && is_array($data['tokens'])) {
            $this->start = is_scalar($data['tokens']['start'] ?? null) ? (string) $data['tokens']['start'] : null;
            $this->end = is_scalar($data['tokens']['end'] ?? null) ? (string) $data['tokens']['end'] : $this->end;
        }
    }

    /**
     * Возвращает значение слота. Если передан ключ — возвращает подзначение из массива.
     *
     * @param string|null $key Ключ внутри значения
     * @param mixed $default Значение по умолчанию или callable
     * @return mixed
     */
    public function value(?string $key = null, mixed $default = null): mixed
    {
        $val = $this->data['value'] ?? null;

        if (!is_array($val)) {
            if ($key === null) {
                if ($default instanceof \Closure) {
                    return Container::getInstance()->call($default);
                }
                return $val ?? $default;
            }
            return $val ?? $default;
        }

        if ($key !== null && array_key_exists($key, $val)) {
            return $val[$key];
        }

        if ($default instanceof \Closure) {
            return Container::getInstance()->call($default);
        }

        return $default;
    }
}