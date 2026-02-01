<?php

namespace Alice\Types\Nlu\Tokens;

/**
 * Обёртка токенов NLU с утилитами (search, nGrams, frequency и т.д.).
 */
class Tokens
{
    /**
     * @param array $data
     */
    public function __construct(protected array $data)
    {
        //
    }

    /**
     * Проверить наличие токена в списке токенов.
     * @param string $token
     * @return bool
     */
    public function has(string $token): bool
    {
        return in_array($token, $this->data['tokens']);
    }

    /**
     * Проверяет, содержится ли подстрока в любом из токенов.
     *
     * @param string $token Подстрока для поиска
     * @return bool
     */
    public function contains(string $token): bool
    {
        foreach ($this->data as $value) {
            if (str_contains($value, $token)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Возвращает набор токенов, содержащих подстроку.
     *
     * @param string $needle Подстрока
     * @return static
     */
    public function search(string $needle): static
    {
        return new static(array_filter($this->data, function($token) use ($needle) {
            return str_contains($token, $needle);
        }));
    }

    /**
     * Применяет callback ко всем токенам и возвращает новую коллекцию.
     *
     * @param callable $callback
     * @return static
     */
    public function map(callable $callback): static
    {
        return new static(array_map($callback, $this->data));
    }

    /**
     * Возвращает все токены как массив.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * Возвращает новую коллекцию, в которой удалены указанные токены.
     *
     * @param array $tokens Токены для удаления
     * @return static
     */
    public function remove(array $tokens): static
    {
        return new static(array_values(array_filter($this->data, function($token) use ($tokens) {
            return !in_array($token, $tokens);
        })));
    }

    /**
     * Генерирует n-граммы из последовательности токенов.
     *
     * @param int $n Размер n-граммы
     * @return array
     */
    public function nGrams(int $n): array
    {
        $ngrams = [];
        $length = count($this->data);

        for ($i = 0; $i < $length - $n + 1; $i++) {
            $ngrams[] = implode(' ', array_slice($this->data, $i, $n));
        }

        return $ngrams;
    }

    /**
     * Вычисляет частоту встречаемости токенов.
     *
     * @return array
     */
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

    /**
     * Возвращает пересечение текущих токенов с другим набором.
     *
     * @param array $otherTokens
     * @return array
     */
    public function intersect(array $otherTokens): array
    {
        return array_intersect($this->data, $otherTokens);
    }

    /**
     * Возвращает разницу между текущими токенами и другим набором.
     *
     * @param array $otherTokens
     * @return array
     */
    public function difference(array $otherTokens): array
    {
        return array_diff($this->data, $otherTokens);
    }

    /**
     * Возвращает количество токенов.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->data);
    }
}