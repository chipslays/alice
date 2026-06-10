<?php

namespace Alice\Types\Nlu\Intents;

class Slot
{
    public readonly ?string $type;

    public readonly ?string $start;

    public readonly ?string $end;

    public readonly mixed $value;

    /**
     * Конструктор слота интента.
     *
     * @param array<string,mixed> $data Данные слота
     */
    public function __construct(protected array $data)
    {
        $this->type = is_scalar($data['type'] ?? null) ? (string) $data['type'] : null;

        if (isset($data['tokens']) && is_array($data['tokens'])) {
            $this->start = is_scalar($data['tokens']['start'] ?? null) ? (string) $data['tokens']['start'] : null;
            $this->end = is_scalar($data['tokens']['end'] ?? null) ? (string) $data['tokens']['end'] : null;
        } else {
            $this->start = null;
            $this->end = null;
        }

        $this->value = $data['value'] ?? null;
    }
}
