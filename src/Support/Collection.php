<?php

declare(strict_types=1);

namespace Alice\Support;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

class Collection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    protected array $items = [];

    /**
     * Initialize a new Collection instance.
     *
     * @param array $items The initial items of the collection.
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * Creates a new instance of the current class with the given items.
     *
     * @param array $items The initial items of the collection.
     *
     * @return static
     */
    public static function make(array $items = []): static
    {
        return new static($items);
    }

    /**
     * Return all items of the collection.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Retrieve an item from the collection by its key.
     *
     * If the key contains a dot (.), it will be treated as a nested key.
     * Otherwise, it will be treated as a top-level key.
     *
     * @param string|int $key The key of the item to retrieve.
     * @param mixed $default The default value to return if the item is not found.
     *
     * @return mixed The retrieved item, or the default value if not found.
     */
    public function get(string|int $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->items)) {
            return $this->items[$key];
        }

        if (!is_string($key) || !str_contains($key, '.')) {
            return $default;
        }

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
     * Set an item in the collection by its key.
     *
     * If the key contains a dot (.), it will be treated as a nested key.
     * Otherwise, it will be treated as a top-level key.
     *
     * @param string|int $key The key of the item to set.
     * @param mixed $value The value of the item to set.
     *
     * @return static The instance of the collection after setting the item.
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
     * Checks if an item exists in the collection by its key.
     *
     * If the key contains a dot (.), it will be treated as a nested key.
     * Otherwise, it will be treated as a top-level key.
     *
     * @param string|int $key The key of the item to check.
     *
     * @return bool True if the item exists, false otherwise.
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
     * Removes an item from the collection by its key.
     *
     * If the key contains a dot (.), it will be treated as a nested key.
     * Otherwise, it will be treated as a top-level key.
     *
     * @param string|int $key The key of the item to remove.
     *
     * @return static The current collection instance.
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

            unset($array[$last]);
        }

        return $this;
    }

    /**
     * Clears the collection, effectively removing all items.
     *
     * @return static The current collection instance.
     */
    public function clear(): static
    {
        $this->items = [];
        return $this;
    }

    /**
     * Merges the given items into the current collection.
     *
     * The items are merged using the `array_replace_recursive` function,
     * which means that if an item with the same key already exists in the collection,
     * it will be replaced with the new item.
     *
     * @param array $items The items to merge into the collection.
     *
     * @return static The current collection instance.
     */
    public function merge(array $items): static
    {
        $this->items = array_replace_recursive($this->items, $items);
        return $this;
    }

    /**
     * Maps the given callback function to each item in the collection.
     *
     * The callback function will receive each item as its first argument.
     * The returned value will replace the original item in the collection.
     *
     * @param callable $callback The callback function to apply to each item.
     *
     * @return static The current collection instance with the mapped items.
     */
    public function map(callable $callback): static
    {
        return new static(array_map($callback, $this->items));
    }

    /**
     * Filters the items in the collection using the given callback function.
     *
     * The callback function will receive each item as its first argument.
     * If the callback function returns a truthy value, the item will be included in the filtered collection.
     * If the callback function returns a falsey value, the item will be excluded from the filtered collection.
     *
     * @param callable $callback The callback function to apply to each item.
     *
     * @return static The filtered collection instance.
     */
    public function filter(callable $callback): static
    {
        return new static(array_filter($this->items, $callback));
    }

    /**
     * Returns the first item in the collection that satisfies the given callback function.
     *
     * If no callback function is given, the first item in the collection is returned.
     * If the collection is empty, the given default value is returned.
     *
     * The callback function will receive each item as its first argument and its key as its second argument.
     * If the callback function returns a truthy value, the item is returned.
     * If the callback function returns a falsey value, the next item is tested.
     *
     * @param callable|null $callback The callback function to apply to each item.
     * @param mixed $default The default value to return if no item satisfies the callback or if the collection is empty.
     *
     * @return mixed The first item that satisfies the callback or the default value if no item does.
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
     * Pushes the given value onto the end of the collection.
     *
     * @param mixed $value The value to push onto the end of the collection.
     *
     * @return static The current collection instance with the pushed value.
     */
    public function push(mixed $value): static
    {
        $this->items[] = $value;
        return $this;
    }

    /**
     * Removes and returns the last item in the collection.
     *
     * @return mixed The last item in the collection.
     */
    public function pop(): mixed
    {
        return array_pop($this->items);
    }

    /**
     * Returns an array representation of the current collection.
     *
     * Recursively calls toArray on all child collections and returns
     * the array representation of the collection.
     *
     * @return array The array representation of the current collection.
     */
    public function toArray(): array
    {
        return array_map(function ($value) {
            return $value instanceof self ? $value->toArray() : $value;
        }, $this->items);
    }

    /**
     * Returns the number of items in the collection.
     *
     * @return int The number of items in the collection.
     */
    public function count(): int
    {
        return count($this->items);
    }

/**
 * Returns an iterator for the current collection.
 *
 * @return Traversable The iterator for the current collection.
 */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Checks if a given offset exists.
     *
     * @param mixed $offset The offset to check
     *
     * @return bool True if the offset exists, false otherwise
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    /**
     * Retrieves the value associated with the given offset.
     *
     * @param mixed $offset The offset to retrieve
     *
     * @return mixed The value associated with the given offset
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    /**
     * Sets the value associated with the given offset.
     *
     * If the offset is null, the value is appended to the current collection.
     * Otherwise, the value is set at the given offset.
     *
     * @param mixed $offset The offset to set
     * @param mixed $value The value to set
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->set($offset, $value);
        }
    }

    /**
     * Removes the value associated with the given offset.
     *
     * @param mixed $offset The offset to remove
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->remove($offset);
    }

    /**
     * {@inheritDoc}
     *
     * Returns the collection as an array that can be serialized to JSON.
     */

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }


    /**
     * Returns the collection as a JSON string.
     *
     * @param int $options The JSON encoding options to use
     *
     * @return string The collection as a JSON string
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Returns the collection as a JSON string.
     *
     * This method is invoked when the object is treated like a string.
     *
     * @return string The collection as a JSON string
     */
    public function __toString(): string
    {
        return $this->toJson();
    }

    /**
     * Magic getter to access items in the collection.
     *
     * @param string $key The key of the item to access
     *
     * @return mixed The value associated with the given key
     */
    public function __get(string $key): mixed
    {
        return $this->get($key);
    }

    /**
     * Magic setter to set items in the collection.
     *
     * @param string $key The key of the item to set
     * @param mixed $value The value to set for the given key
     */
    public function __set(string $key, mixed $value): void
    {
        $this->set($key, $value);
    }

    /**
     * Checks if an item exists in the collection by its key.
     *
     * This method is invoked when checking if a property is set, either via
     * `isset($collection->key)` or `empty($collection->key)`.
     *
     * @param string $key The key of the item to check
     *
     * @return bool True if the item exists, false otherwise
     */
    public function __isset(string $key): bool
    {
        return $this->has($key);
    }

    /**
     * Magic unset to remove items from the collection.
     *
     * This method is invoked when unsetting a property, either via
     * `unset($collection->key)` or via a property being unset in a
     * foreach loop.
     *
     * @param string $key The key of the item to remove
     */
    public function __unset(string $key): void
    {
        $this->remove($key);
    }
}
