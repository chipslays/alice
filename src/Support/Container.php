<?php

declare(strict_types=1);

namespace Alice\Support;

use Closure;
use Exception;
use Psr\Container\ContainerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use Throwable;

/**
 * Базовая реализация исключений PSR-11, если пакет psr/container не установлен.
 * Если установлен, эти классы можно убрать или наследовать от вендорных.
 */
class ContainerException extends Exception implements ContainerExceptionInterface {}
class NotFoundException extends ContainerException implements NotFoundExceptionInterface {}

class Container implements ContainerInterface
{
    /** @var array<string, array{factory: Closure, shared: bool}> */
    private array $definitions = [];

    /** @var array<string, object> */
    private array $instances = [];

    /** @var array<string, bool> */
    private array $resolving = [];

    /**
     * Регистрация сервиса (каждый раз новый экземпляр).
     */
    public function bind(string $id, string|Closure|null $concrete = null): void
    {
        $this->definitions[$id] = [
            'factory' => $this->normalizeConcrete($id, $concrete),
            'shared' => false,
        ];
    }

    /**
     * Регистрация синглтона (один экземпляр на всё время).
     */
    public function singleton(string $id, string|Closure|null $concrete = null): void
    {
        $this->definitions[$id] = [
            'factory' => $this->normalizeConcrete($id, $concrete),
            'shared' => true,
        ];
    }

    /**
     * Регистрация готового объекта.
     * Критически важно для прокидывания $this, $context и конфигов.
     */
    public function instance(string $id, object $instance): void
    {
        $this->instances[$id] = $instance;
    }

    /**
     * Получение сервиса (PSR-11).
     */
    public function get(string $id): mixed
    {
        // 1. Если уже есть готовый инстанс — возвращаем
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        // 2. Если есть определение — создаем
        if (isset($this->definitions[$id])) {
            $definition = $this->definitions[$id];

            if (isset($this->resolving[$id])) {
                throw new ContainerException("Circular dependency detected: $id");
            }

            $this->resolving[$id] = true;

            try {
                $object = $definition['factory']($this);
            } finally {
                unset($this->resolving[$id]);
            }

            if ($definition['shared']) {
                $this->instances[$id] = $object;
            }

            return $object;
        }

        // 3. Если определений нет — пытаемся угадать через Reflection (Autowiring)
        return $this->resolve($id);
    }

    public function has(string $id): bool
    {
        return isset($this->instances[$id])
            || isset($this->definitions[$id])
            || class_exists($id);
    }

    /**
     * Вызов функции или метода с автоматической подстановкой зависимостей.
     *
     * @param callable|array|string $callable Функция для вызова
     * @param array $parameters Ручные параметры ['name' => 'value']
     */
    public function call(callable|array|string $callable, array $parameters = []): mixed
    {
        $reflector = $this->getReflector($callable);
        $dependencies = $this->resolveDependencies($reflector, $parameters);

        return $callable(...$dependencies);
    }

    /**
     * Создание класса через Reflection.
     */
    private function resolve(string $className): object
    {
        try {
            $reflector = new ReflectionClass($className);
        } catch (ReflectionException $e) {
            throw new NotFoundException("Target class [$className] does not exist.", 0, $e);
        }

        if (!$reflector->isInstantiable()) {
            throw new ContainerException("Class [$className] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return $reflector->newInstance();
        }

        $dependencies = $this->resolveDependencies($constructor);

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * Разрешение списка зависимостей для метода или конструктора.
     */
    private function resolveDependencies(ReflectionFunctionAbstract $method, array $manualParameters = []): array
    {
        $dependencies = [];

        foreach ($method->getParameters() as $param) {
            $name = $param->getName();

            // 1. Если параметр передан вручную — берем его
            if (array_key_exists($name, $manualParameters)) {
                $dependencies[] = $manualParameters[$name];
                continue;
            }

            // 2. Пытаемся разрешить через контейнер
            try {
                $dependencies[] = $this->resolveParameter($param);
            } catch (ContainerException $e) {
                // Если не смогли разрешить, но есть дефолтное значение — берем его
                if ($param->isDefaultValueAvailable()) {
                    $dependencies[] = $param->getDefaultValue();
                } elseif ($param->allowsNull()) {
                    $dependencies[] = null;
                } else {
                    throw $e;
                }
            }
        }

        return $dependencies;
    }

    /**
     * Логика разрешения одного параметра (поддержка PHP 8.0-8.4 типов).
     */
    private function resolveParameter(ReflectionParameter $param): mixed
    {
        $type = $param->getType();

        // Если типа нет или это примитив без дефолта — ошибка
        if (!$type) {
            throw new ContainerException("Missing type hint for param [$$param->name]");
        }

        // Union (A|B) или Intersection (A&B) типы
        if ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) {
            foreach ($type->getTypes() as $subType) {
                if ($subType instanceof ReflectionNamedType && !$subType->isBuiltin()) {
                    try {
                        return $this->get($subType->getName());
                    } catch (NotFoundException) {
                        continue;
                    }
                }
            }
            throw new ContainerException("Cannot resolve complex type for [$$param->name]");
        }

        // Обычные типы (NamedType)
        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            return $this->get($type->getName());
        }

        throw new ContainerException("Cannot resolve primitive parameter [$$param->name]");
    }

    private function normalizeConcrete(string $id, string|Closure|null $concrete): Closure
    {
        if ($concrete === null) {
            return fn (self $c) => $c->resolve($id);
        }

        if (is_string($concrete)) {
            return fn (self $c) => $c->resolve($concrete);
        }

        return $concrete;
    }

    private function getReflector(callable|array|string $callable): ReflectionFunctionAbstract
    {
        if (is_array($callable)) {
            return new ReflectionMethod($callable[0], $callable[1]);
        }

        return new ReflectionFunction($callable);
    }
}
