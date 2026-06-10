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
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use ReflectionIntersectionType;

class ContainerException extends Exception implements ContainerExceptionInterface {}
class NotFoundException extends ContainerException implements NotFoundExceptionInterface {}

/**
 * Простой DI-контейнер с поддержкой singleton/bind/instance и автозависимостей через Reflection.
 *
 * @phpstan-consistent-constructor
 */
class Container implements ContainerInterface
{
    /**
     * Глобальный экземпляр контейнера.
     */
    protected static ?Container $instance = null;

    /** @var array<string, array{factory: Closure, shared: bool}> */
    private array $definitions = [];

    /** @var array<string, object> */
    private array $instances = [];

    /** @var array<string, bool> */
    private array $resolving = [];

    /**
     * Получить глобальный экземпляр контейнера.
     *
     * @return Container
     */
    public static function getInstance(): Container
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Установить текущий экземпляр контейнера (полезно в тестах).
     *
     * @param Container|null $container
     * @return Container|null
     */
    public static function setInstance(?Container $container): ?Container
    {
        return self::$instance = $container;
    }

    /**
     * Сбросить инстанс (полезно для тестов).
     *
     * @return void
     */
    public static function flushInstance(): void
    {
        self::$instance = null;
    }

    /**
     * Регистрация сервиса (каждый раз новый экземпляр).
     *
     * @param string $id
     * @param string|Closure|null $concrete
     * @return void
     */
    public function bind(string $id, string|Closure|null $concrete = null): void
    {
        // Удаляем старые инстансы, если переопределяем биндинг
        unset($this->instances[$id]);

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
        unset($this->instances[$id]);

        $this->definitions[$id] = [
            'factory' => $this->normalizeConcrete($id, $concrete),
            'shared' => true,
        ];
    }

    /**
     * Регистрация готового объекта.
     */
    public function instance(string $id, object $instance): void
    {
        $this->instances[$id] = $instance;
    }

    /**
     * Получение сервиса (PSR-11).
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    /**
     * Получение сервиса (PSR-11).
     *
     * @param string $id
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
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

    /**
     * Проверяет, зарегистрирован ли сервис в контейнере или существует ли класс.
     *
     * @param string $id Идентификатор сервиса или имя класса
     * @return bool
     */
    public function has(string $id): bool
    {
        return isset($this->instances[$id])
            || isset($this->definitions[$id])
            || class_exists($id);
    }

    /**
     * Вызов функции, замыкания, метода класса или invokable-класса
     * с автоматическим внедрением зависимостей.
     */
    /**
     * @param callable|array<string,mixed>|array<int,mixed>|string $callable
     * @param array<string,mixed>|array<int,mixed> $parameters
     * @return mixed
     */
    public function call(callable|array|string $callable, array $parameters = []): mixed
    {
        // 1. Подготовка callable
        $callable = $this->prepareCallable($callable);

        // 2. Получение рефлексии
        $reflector = $this->getReflector($callable);

        // 3. Резолвинг зависимостей
        $dependencies = $this->resolveDependencies($reflector, $parameters);

        // 4. Выполнение
        if (!is_callable($callable)) {
            throw new ContainerException("Target is not callable.");
        }

        /** @var callable $call */
        $call = $callable;
        return $call(...$dependencies);
    }

    /**
     * Превращает "сырой" хендлер в валидный PHP callable,
     * попутно создавая объекты через контейнер.
     *
     * @param callable|array<string,mixed>|array<int,mixed>|string $callable
    * @return callable|array<int,mixed>|string
     */
    private function prepareCallable(callable|array|string $callable): callable|array|string
    {
        // Если это строка 'Class@method' или 'Class::method' -> превращаем в массив
        if (is_string($callable)) {
            if (str_contains($callable, '@')) {
                $callable = explode('@', $callable);
            } elseif (str_contains($callable, '::')) {
                $callable = explode('::', $callable);
            }
        }

        // Если это массив ['Class', 'method'] или ['Class']
        if (is_array($callable) && isset($callable[0])) {
            $class = $callable[0];

            // Если второго элемента не было (формат ['Class']), добавляем __invoke
            $method = $callable[1] ?? '__invoke';

            // Если первый элемент - строка (имя класса) — создаем объект через контейнер
            if (is_string($class)) {
                $object = $this->get($class);
            } else {
                $object = $class;
            }

            /** @var object $object */
            // Превращаем в замыкание, чтобы иметь стабильный callable-тип
            return function(...$args) use ($object, $method) {
                return $object->{$method}(...$args);
            };
        }

        // Если это просто строка 'ClassName', и такой класс существует -> Invokable
        if (is_string($callable) && class_exists($callable)) {
            return function(...$args) use ($callable) {
                /** @var callable $invokable */
                $invokable = $this->get($callable);
                return $invokable(...$args);
            };

        }

        return $callable;
    }

    /**
     * Создание класса через Reflection.
     *
     * @param string $className
     */
    private function resolve(string $className): object
    {
        if (!class_exists($className)) {
            throw new NotFoundException("Target class [$className] does not exist.");
        }

        $reflector = new ReflectionClass($className);

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
     * Разрешение списка зависимостей.
     */
    /**
     * @param ReflectionFunctionAbstract $method
     * @param array<string,mixed>|array<int,mixed> $manualParameters
     * @return array<int,mixed>
     */
    private function resolveDependencies(ReflectionFunctionAbstract $method, array $manualParameters = []): array
    {
        $dependencies = [];

        foreach ($method->getParameters() as $param) {
            $name = $param->getName();

            // 1. Ручные параметры
            if (array_key_exists($name, $manualParameters)) {
                $dependencies[] = $manualParameters[$name];
                continue;
            }

            // 2. Через контейнер
            try {
                $dependencies[] = $this->resolveParameter($param);
            } catch (ContainerExceptionInterface $e) {
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
     * Разрешение одного параметра.
     */
    private function resolveParameter(ReflectionParameter $param): mixed
    {
        $type = $param->getType();

        if (!$type) {
            if ($this->has($param->getName())) {
                return $this->get($param->getName());
            }
            throw new ContainerException("Missing type hint for param [$$param->name]");
        }

        if ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) {
            foreach ($type->getTypes() as $subType) {
                if ($subType instanceof ReflectionNamedType && !$subType->isBuiltin()) {
                    try {
                        return $this->get($subType->getName());
                    } catch (ContainerExceptionInterface) {
                        continue;
                    }
                }
            }
            throw new ContainerException("Cannot resolve complex type for [$$param->name]");
        }

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

    /**
     * @param callable|array<string,mixed>|array<int,mixed>|string $callable
     * @return ReflectionFunctionAbstract
     */
    private function getReflector(callable|array|string $callable): ReflectionFunctionAbstract
    {
        if (is_string($callable) && str_contains($callable, '::')) {
            $callable = explode('::', $callable);
        }

        if (is_array($callable)) {
            $classOrObj = $callable[0] ?? null;
            $method = $callable[1] ?? '__invoke';

            if (!is_string($classOrObj) && !is_object($classOrObj)) {
                throw new ContainerException('Unsupported callable array format.');
            }

            return new ReflectionMethod($classOrObj, $method);
        }

        // Исправление: Поддержка объектов с __invoke
        if (is_object($callable) && !$callable instanceof Closure) {
            return new ReflectionMethod($callable, '__invoke');
        }

        if ($callable instanceof Closure) {
            return new ReflectionFunction($callable);
        }

        if (is_string($callable)) {
            return new ReflectionFunction($callable);
        }

        throw new ContainerException('Unsupported callable type for reflector.');
    }
}
