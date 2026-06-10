<?php

namespace Alice\Types\Nlu\Tokens;

/**
 * Обёртка токенов NLU с утилитами (search, nGrams, frequency и т.д.).
 *
 * @phpstan-consistent-constructor
 * @property array<int,string> $data
 */
class Tokens
{
    /**
     * @param array<int,mixed>|array{tokens:array<int,mixed>} $data Сырые данные токенов или массив с ключом 'tokens'
     */
    public function __construct(protected array $data)
    {
        // Normalize possible shape: ['tokens' => [...]] or plain list
        if (isset($this->data['tokens']) && is_array($this->data['tokens'])) {
            $this->data = array_values($this->data['tokens']);
        } else {
            $this->data = array_values($this->data);
        }

        // Нормализуем токены в строки, чтобы убрать неявные union-типы
        $tokens = array_map(function($v) {
            if (is_array($v)) {
                // Вложенный массив — склеиваем значения через пробел
                return implode(' ', array_map(fn($x) => is_scalar($x) ? (string) $x : '', array_values($v)));
            }
            return is_scalar($v) ? (string) $v : '';
        }, $this->data);

        /** @var array<int,string> $tokens */
        $this->data = $tokens;
    }

    /**
     * Проверить наличие токена в списке токенов.
     * @param string $token
     * @return bool
     */
    public function has(string $token): bool
    {
        return in_array($token, $this->data, true);
    }

    /**
     * Проверяет, содержится ли подстрока в любом из токенов.
     *
     * @param string $token Подстрока для поиска
     * @return bool
     */
    public function contains(string $token): bool
    {
        /** @var array<int,string> $tokens */
        $tokens = $this->data;

        foreach ($tokens as $value) {
            if (str_contains((string) $value, $token)) {
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
        $result = [];

        /** @var array<int,string> $tokens */
        $tokens = $this->data;

        foreach ($tokens as $token) {
            if (str_contains((string) $token, $needle)) {
                $result[] = (string) $token;
            }
        }
        return new static($result);
    }

    /**
     * Применяет callback ко всем токенам и возвращает новую коллекцию.
     *
     * @param callable(string,int):string $callback Функция преобразования (token, index) должна возвращать строку
     * @return static
     */
    public function map(callable $callback): static
    {
        $result = [];
        /** @var array<int,string> $tokens */
        $tokens = array_values($this->data);

        foreach ($tokens as $index => $token) {
            $tokenStr = (string) $token;
            $result[] = $callback($tokenStr, $index);
        }

        return new static($result);
    }

    /**
     * Возвращает все токены как массив.
     *
     * @return array<int,string>
     */
    public function all(): array
    {
        /** @var array<int,string> $vals */
        $vals = array_values($this->data);
        return array_map(function($v) {
            return (string) $v;
        }, $vals);
    }

    /**
     * Возвращает новую коллекцию, в которой удалены указанные токены.
     *
     * @param array<int,string> $tokens Токены для удаления
     * @return static
     */
    public function remove(array $tokens): static
    {
        $result = [];
        /** @var array<int,string> $selfTokens */
        $selfTokens = $this->data;

        foreach ($selfTokens as $token) {
            $tokenStr = (string) $token;
            if (!in_array($tokenStr, $tokens, true)) {
                $result[] = $tokenStr;
            }
        }
        return new static($result);
    }

    /**
     * Генерирует n-граммы из последовательности токенов.
     *
     * @param int $n Размер n-граммы
     * @return array<int,string>
     */
    public function nGrams(int $n): array
    {
        $ngrams = [];
        /** @var array<int,string> $data */
        $data = $this->data;
        $length = count($data);

        for ($i = 0; $i < $length - $n + 1; $i++) {
            /** @var array<int,string> $slice */
            $slice = array_slice($data, $i, $n);
            $ngrams[] = implode(' ', array_map(function($v) {
                return (string) $v;
            }, array_values($slice)));
        }

        return $ngrams;
    }

    /**
     * Вычисляет частоту встречаемости токенов.
     *
     * @return array<string,int>
     */
    public function frequency(): array
    {
        $frequency = [];

        /** @var array<int,string> $tokens */
        $tokens = $this->data;

        foreach ($tokens as $token) {
            $t = (string) $token;
            if (isset($frequency[$t])) {
                $frequency[$t]++;
            } else {
                $frequency[$t] = 1;
            }
        }

        return $frequency;
    }

    /**
     * Возвращает пересечение текущих токенов с другим набором.
     *
     * @param array<int,string> $otherTokens
     * @return array<int,string>
     */
    public function intersect(array $otherTokens): array
    {
        /** @var array<int,string> $arr */
        $arr = array_values($this->data);
        /** @var array<int,string> $self */
        $self = array_map(function($v) {
            return (string) $v;
        }, $arr);
        /** @var array<int,string> $other */
        $other = array_map(fn($v) => (string) $v, array_values($otherTokens));
        return array_values(array_intersect($self, $other));
    }

    /**
     * Возвращает разницу между текущими токенами и другим набором.
     *
     * @param array<int,string> $otherTokens
     * @return array<int,string>
     */
    public function difference(array $otherTokens): array
    {
        /** @var array<int,string> $arr */
        $arr = array_values($this->data);
        /** @var array<int,string> $self */
        $self = array_map(function($v) {
            return (string) $v;
        }, $arr);
        /** @var array<int,string> $other */
        $other = array_map(fn($v) => (string) $v, array_values($otherTokens));
        return array_values(array_diff($self, $other));
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
