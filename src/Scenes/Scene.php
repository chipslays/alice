<?php

namespace Alice\Scenes;

use Alice\Context;
use Alice\Events\Dispatcher;
use Alice\Support\Container;
use Alice\Traits\Eventable;
use Closure;

class Scene
{
    use Eventable;

    // Диспетчер событий конкретно этой сцены
    protected Dispatcher $dispatcher;

    protected ?Closure $onEnterHandler = null;
    protected ?Closure $onLeaveHandler = null;

    public function __construct(public readonly string $name)
    {
        $this->dispatcher = new Dispatcher;
    }

    public function onEnter(Closure $handler): self
    {
        $this->onEnterHandler = $handler;
        return $this;
    }

    public function onLeave(Closure $handler): self
    {
        $this->onLeaveHandler = $handler;
        return $this;
    }

    // Реализация абстрактного метода из Eventable
    protected function getEventDispatcher(): Dispatcher
    {
        return $this->dispatcher;
    }

    public function enter(): void
    {
        if ($this->onEnterHandler) {
            // Вызываем через контейнер для инъекции зависимостей
            Container::getInstance()
                ->call($this->onEnterHandler, [
                    'context' => Container::getInstance()->get(Context::class),
                    'scene' => $this,
                ]);
        }
    }

    public function leave(): void
    {
        if ($this->onLeaveHandler) {
            Container::getInstance()->call($this->onLeaveHandler, ['context' => Container::getInstance()->get(Context::class), 'scene' => $this]);
        }
    }

    public function dispatch(): bool
    {
        return $this->dispatcher->dispatch();
    }
}
