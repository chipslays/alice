<?php

declare(strict_types=1);

namespace Alice\Support;

use ArrayAccess;
use ArrayIterator;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * Общая коллекция.
 *
 * @phpstan-consistent-constructor
 * @template TValue
 * @implements ArrayAccess<int|string,TValue>
 * @implements IteratorAggregate<int|string,TValue>
 */
class Collection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    /** @var array<int|string,TValue> */
    protected array $items = [];

    /**
     * Создаёт новый экземпляр коллекции.
     *
     * @param array<int|string,TValue> $items Начальные элементы коллекции
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * Вернуть все элементы коллекции.
     *
     * @return array<int|string,TValue>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Получить элемент коллекции по ключу.
     *
     * Поддерживает вложенные ключи через точку.
     *
     * @param string|int $key Ключ элемента
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    public function get(string|int $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->items)) {
            return $this->items[$key];
        }

        if (!is_string($key) || !str_contains($key, '.')) {
            return $default;
        }

        /** @var array<int|string,mixed> $array */
        $array = $this->items;
        foreach (explode('.', $key) as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }

        return $array;
    }

    /**
     * Установить значение по ключу в коллекции.
     *
     * Поддерживает вложенные ключи через точку.
     *
     * @param string|int $key
     * @param mixed $value
     * @return static
     */
    public function set(string|int $key, mixed $value): static
    {
        if (is_int($key)) {
             $this->items[$key] = $value;
             return $this;
        }

        $keys = explode('.', $key);
        $array = &$this->items;

        foreach ($keys as $i => $segment) {
            if (count($keys) === 1) {
                break;
            }

            unset($keys[$i]);

            if (!isset($array[$segment]) || !is_array($array[$segment])) {
                $array[$segment] = [];
            }

            $array = &$array[$segment];
        }

        $array[array_shift($keys)] = $value;

        return $this;
    }

    /**
     * Проверяет существование элемента по ключу.
     *
     * Поддерживает вложенные ключи через точку.
     *
     * @param string|int $key
     * @return bool
     */
    public function has(string|int $key): bool
    {
        if (array_key_exists($key, $this->items)) {
            return true;
        }

        if (!is_string($key) || !str_contains($key, '.')) {
            return false;
        }

        $array = $this->items;
        foreach (explode('.', $key) as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Удаляет элемент по ключу.
     *
     * Поддерживает вложенные ключи через точку.
     *
     * @param string|int $key
     * @return static
     */
    public function remove(string|int $key): static
    {
        if (array_key_exists($key, $this->items)) {
            unset($this->items[$key]);
            return $this;
        }

        if (is_string($key) && str_contains($key, '.')) {
            $keys = explode('.', $key);
            $array = &$this->items;
            $last = array_pop($keys);

            foreach ($keys as $segment) {
                if (!is_array($array) || !array_key_exists($segment, $array)) {
                    return $this;
                }
                $array = &$array[$segment];
            }

            if (is_array($array) && array_key_exists($last, $array)) {
                unset($array[$last]);
            }
        }

        return $this;
    }

    /**
     * Очищает коллекцию от всех элементов.
     *
     * @return static
     */
    public function clear(): static
    {
        $this->items = [];
        return $this;
    }

    /**
     * Сливает переданные элементы в текущую коллекцию (array_replace_recursive).
     *
     * @param array<int|string,TValue> $items
     * @return $this
     */
    public function merge(array $items): static
    {
        $this->items = array_replace_recursive($this->items, $items);
        return $this;
    }

    /**
     * Применяет callback ко всем элементам коллекции и возвращает новую коллекцию.
     *
     * @template TReturn
     * @param callable(TValue,int|string):TReturn $callback Функция преобразования (value, key): TReturn
     * @return static<TReturn>
     */
    public function map(callable $callback): static
    {
        $result = [];
        foreach ($this->items as $key => $value) {
            $result[] = $callback($value, $key);
        }

        return new static($result);
    }

    /**
     * Отфильтровывает элементы коллекции с помощью callback-функции.
     *
     * Callback получает значение элемента и его ключ. Если функция возвращает истинное
     * значение — элемент остается в результирующей коллекции.
     *
     * @param callable(TValue,int|string):bool $callback Функция фильтрации (value, key): bool
     * @return static<TValue>
     */
    public function filter(callable $callback): static
    {
        $result = [];
        foreach ($this->items as $key => $value) {
            if ($callback($value, $key)) {
                $result[$key] = $value;
            }
        }

        return new static($result);
    }

    /**
     * Возвращает первый элемент, удовлетворяющий callback, или первый элемент коллекции.
     *
     * @param callable|null $callback
     * @param mixed $default
     * @return mixed
     */
    public function first(?callable $callback = null, mixed $default = null): mixed
    {
        if (is_null($callback)) {
            if (empty($this->items)) {
                return $default;
            }
            return reset($this->items);
        }

        foreach ($this->items as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return $default;
    }

    /**
     * Добавляет элемент в конец коллекции.
     *
     * @param mixed $value
     * @return static
     */
    public function push(mixed $value): static
    {
        $this->items[] = $value;
        return $this;
    }

    /**
     * Удаляет и возвращает последний элемент коллекции.
     *
     * @return mixed
     */
    public function pop(): mixed
    {
        return array_pop($this->items);
    }

    /**
     * Увеличивает числовое значение элемента коллекции.
     *
     * @param string|int $key Ключ элемента
     * @param int|float $amount Величина инкремента
     * @return int|float Новое значение элемента
     * @throws InvalidArgumentException Если текущее значение не является числом
     */
    public function increment(string|int $key, int|float $amount = 1): int|float
    {
        $value = $this->get($key, 0);

        if (!is_numeric($value)) {
            throw new InvalidArgumentException("Value for key '{$key}' is not numeric.");
        }

        $newValue = $value + $amount;
        $this->set($key, $newValue);

        return $newValue;
    }

    /**
     * Уменьшает числовое значение элемента коллекции.
     *
     * @param string|int $key Ключ элемента
     * @param int|float $amount Величина декремента
     * @return int|float Новое значение элемента
     * @throws InvalidArgumentException Если текущее значение не является числом
     */
    public function decrement(string|int $key, int|float $amount = 1): int|float
    {
        return $this->increment($key, -$amount);
    }

    /**
     * Преобразовать коллекцию в массив (рекурсивно вызывая toArray у вложенных коллекций).
     *
     * @return array<int|string,mixed>
     */
    public function toArray(): array
    {
        return array_map(function ($value) {
            return $value instanceof self ? $value->toArray() : $value;
        }, $this->items);
    }

    /**
     * Количество элементов в коллекции.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Возвращает итератор для коллекции.
     *
     * @return Traversable<int|string,TValue>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Проверяет существование значения по смещению (offset).
     *
     * @param mixed $offset Смещение (ключ)
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        if (!is_string($offset) && !is_int($offset)) {
            return false;
        }
        return $this->has($offset);
    }

    /**
     * Получить значение по смещению (offset).
     *
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        if (!is_string($offset) && !is_int($offset)) {
            return null;
        }
        return $this->get($offset);
    }

    /**
     * Устанавливает значение по смещению (offset).
     *
     * Если `$offset` равен null — значение будет добавлено в конец коллекции,
     * иначе — установлено по указанному ключу.
     *
     * @param mixed $offset Смещение (ключ) или null для добавления
     * @param mixed $value Устанавливаемое значение
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_null($offset)) {
            $this->items[] = $value;
            return;
        }

        if (!is_string($offset) && !is_int($offset)) {
            throw new InvalidArgumentException('Offset must be a string or int');
        }

        $this->set($offset, $value);
    }

    /**
     * Удаляет значение по смещению (offset).
     *
     * @param mixed $offset Смещение (ключ)
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        if (!is_string($offset) && !is_int($offset)) {
            return;
        }
        $this->remove($offset);
    }

    /**
     * {@inheritDoc}
     *
     * Преобразует коллекцию в массив, пригодный для сериализации в JSON.
     *
     * @return array<int|string,mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Преобразовать коллекцию в JSON строку.
     *
     * @param int $options Опции кодирования JSON
     * @return string
     */
    public function toJson(int $options = 0): string
    {
        $json = json_encode($this->jsonSerialize(), $options);
        return $json === false ? '' : $json;
    }

    /**
     * Преобразовать коллекцию в JSON при приведении к строке.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toJson();
    }

    /**
     * Магический геттер для доступа к элементам коллекции.
     *
     * @param string $key
     * @return mixed
     */
    public function __get(string $key): mixed
    {
        return $this->get($key);
    }

    /**
     * Магический сеттер для установки значения по ключу.
     *
     * @param string $key
     * @param mixed $value
     */
    public function __set(string $key, mixed $value): void
    {
        $this->set($key, $value);
    }

    /**
     * Проверяет существование ключа (используется при isset()/empty()).
     *
     * @param string $key
     * @return bool
     */
    public function __isset(string $key): bool
    {
        return $this->has($key);
    }

    /**
     * Магический unset для удаления элемента.
     *
     * @param string $key
     */
    public function __unset(string $key): void
    {
        $this->remove($key);
    }
}
