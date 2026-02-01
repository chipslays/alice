<?php

declare(strict_types=1);

namespace Alice\Support;

use Closure;
use Alice\Support\Container;

class Pipeline
{
    /**
     * Объект, который пропускаем через трубы (middleware).
     *
     * @var mixed
     */
    protected mixed $passable;

    /**
     * Список труб (middleware).
     *
     * @var array<int, callable|string|object>
     */
    protected array $pipes = [];

    /**
     * Имя метода, который вызывается у pipe-классов.
     */
    protected string $method = 'handle';

    /**
     * Установить объект для обработки.
     */
    public function send(mixed $passable): self
    {
        $this->passable = $passable;
        return $this;
    }

    /**
     * Установить список middleware.
     *
     * @param array<int, callable|string|object> $pipes
     */
    public function through(array $pipes): self
    {
        $this->pipes = $pipes;
        return $this;
    }

    /**
     * Установить имя метода для вызова (по умолчанию handle).
     */
    public function via(string $method): self
    {
        $this->method = $method;
        return $this;
    }

    /**
     * Запустить выполнение pipeline и в конце выполнить $destination.
     */
    public function then(Closure $destination): mixed
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes),
            $this->carry(),
            $this->prepareDestination($destination)
        );

        return $pipeline($this->passable);
    }

    /**
     * Просто возвращает результат после прохода всех труб (без финального колбэка).
     */
    public function thenReturn(): mixed
    {
        return $this->then(fn ($passable) => $passable);
    }

    /**
     * Основная магия сборки замыканий.
     *
     * @return Closure
     */
    protected function carry(): Closure
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                if (is_callable($pipe)) {
                    // Если это Closure или callable
                    return $pipe($passable, $stack);
                } elseif (!is_object($pipe)) {
                    // Если это строка (имя класса)
                    $pipe = Container::getInstance()->get($pipe);
                    /** @var object $pipe */
                }

                // Вызов метода handle($passable, $next)
                $method = $this->method;
                return $pipe->{$method}($passable, $stack);
            };
        };
    }

    protected function prepareDestination(Closure $destination): Closure
    {
        return function ($passable) use ($destination) {
            return $destination($passable);
        };
    }
}
