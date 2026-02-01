<?php

namespace Alice\Scenes;

use Alice\Context;
use Alice\Events\Dispatcher;
use Alice\Support\Container;
use Alice\Traits\Eventable;
use Closure;

/**
 * Представление сцены — контейнера обработчиков onEnter/onLeave и локального dispatcher'а.
 */
class Scene
{
    use Eventable;

    // Диспетчер событий конкретно этой сцены
    protected Dispatcher $dispatcher;

    protected ?Closure $onEnterHandler = null;
    protected ?Closure $onLeaveHandler = null;

    /**
     * Создает сцену с именем и локальным диспетчером событий.
     *
     * @param string $name Имя сцены
     */
    public function __construct(public readonly string $name)
    {
        $this->dispatcher = new Dispatcher;
    }

    /**
     * Регистрирует обработчик входа в сцену.
     *
     * @param Closure $handler
     * @return $this
     */
    public function onEnter(Closure $handler): self
    {
        $this->onEnterHandler = $handler;
        return $this;
    }

    /**
     * Регистрирует обработчик выхода из сцены.
     *
     * @param Closure $handler
     * @return $this
     */
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

    /**
     * Выполняет onEnter обработчик (если есть) через контейнер.
     *
     * @return void
     */
    public function enter(): void
    {
        if ($this->onEnterHandler) {
            // Вызываем через контейнер для инъекции зависимостей
            /** @var Context $context */
            $context = Container::getInstance()->get(Context::class);
            Container::getInstance()
                ->call($this->onEnterHandler, [
                    'context' => $context,
                    'scene' => $this,
                ]);
        }
    }

    /**
     * Выполняет onLeave обработчик (если есть).
     *
     * @return void
     */
    public function leave(): void
    {
        if ($this->onLeaveHandler) {
            /** @var Context $context */
            $context = Container::getInstance()->get(Context::class);
            Container::getInstance()->call($this->onLeaveHandler, ['context' => $context, 'scene' => $this]);
        }
    }

    /**
     * Диспетчер сцены запускает зарегистрированные события и возвращает флаг handled.
     *
     * @return bool
     */
    public function dispatch(): bool
    {
        return $this->dispatcher->dispatch();
    }
}
