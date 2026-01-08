<?php

namespace Alice\Types\Nlu\Tokens;

class Tokens
{
    public function __construct(protected array $data)
    {
        //
    }

    public function has(string $token): bool
    {
        return in_array($token, $this->data['tokens']);
    }

    public function contains(string $token): bool
    {
        foreach ($this->data as $value) {
            if (str_contains($value, $token)) {
                return true;
            }
        }

        return false;
    }

    public function search(string $needle): static
    {
        return new static(array_filter($this->data, function($token) use ($needle) {
            return str_contains($token, $needle);
        }));
    }

    public function map(callable $callback): static
    {
        return new static(array_map($callback, $this->data));
    }

    public function all(): array
    {
        return $this->data;
    }

    public function remove(array $tokens): static
    {
        return new static(array_values(array_filter($this->data, function($token) use ($tokens) {
            return !in_array($token, $tokens);
        })));
    }

    public function nGrams(int $n): array
    {
        $ngrams = [];
        $length = count($this->data);

        for ($i = 0; $i < $length - $n + 1; $i++) {
            $ngrams[] = implode(' ', array_slice($this->data, $i, $n));
        }

        return $ngrams;
    }

    public function frequency(): array
    {
        $frequency = [];

        foreach ($this->data as $token) {
            if (isset($frequency[$token])) {
                $frequency[$token]++;
            } else {
                $frequency[$token] = 1;
            }
        }

        return $frequency;
    }

    public function intersect(array $otherTokens): array
    {
        return array_intersect($this->data, $otherTokens);
    }

    public function difference(array $otherTokens): array
    {
        return array_diff($this->data, $otherTokens);
    }

    public function count(): int
    {
        return count($this->data);
    }
}