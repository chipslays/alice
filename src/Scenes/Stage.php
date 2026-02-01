<?php

namespace Alice\Scenes;

use Alice\Context;
use Alice\Events\Dispatcher;
use Alice\Support\Container;
use Closure;

/**
 * Коллекция сцен и логика переключения между ними (enter/leave/dispatch).
 */
class Stage
{
    /** @var array<string, Scene> */
    protected array $scenes = [];

    /**
     * Регистрирует сцену по имени через callback.
     *
     * @param string $name
     * @param Closure $callback
     * @return void
     */
    public function register(string $name, Closure $callback): void
    {
        $scene = new Scene($name);
        $callback($scene);
        $this->scenes[$name] = $scene;
    }

    /**
     * Получить сцену по имени (или null).
     *
     * @param string $name
     * @return Scene|null
     */
    public function get(string $name): ?Scene
    {
        return $this->scenes[$name] ?? null;
    }

    /**
     * Вызывает dispatch текущей сцены, если она существует.
     *
     * @return bool
     */
    public function dispatch(): bool
    {
        /** @var Context $context */
        $context = Container::getInstance()->get(Context::class);
        $currentSceneName = $context->get('state.session.$scene');

        if (!is_string($currentSceneName) || $currentSceneName === '' || !isset($this->scenes[$currentSceneName])) {
            return false;
        }

        $scene = $this->scenes[$currentSceneName];

        return $scene->dispatch();
    }

    /**
     * Вход в сцену по имени. Сохраняет сцену в состоянии сессии и вызывает
     * onLeave для предыдущей и onEnter для новой сцены при необходимости.
     *
     * @param string $sceneName Имя сцены
     * @return void
     */
    public function enter(string $sceneName): void
    {
        /** @var Context $context */
        $context = Container::getInstance()->get(Context::class);

        $oldSceneName = $context->get('state.session.$scene');

        // Если уже были в какой-то сцене — вызываем onLeave
        if (is_string($oldSceneName) && $oldSceneName !== '' && isset($this->scenes[$oldSceneName])) {
            $this->scenes[$oldSceneName]->leave();
        }

        // Сохраняем новую сцену в сессию
        $context->set('state.session.$scene', $sceneName);

        // Вызываем onEnter новой сцены
        if (isset($this->scenes[$sceneName])) {
            $this->scenes[$sceneName]->enter();
        }
    }

    /**
     * Выход из текущей сцены. Вызывает onLeave, если сцена существует, и сбрасывает
     * состояние текущей сцены в сессии.
     *
     * @return void
     */
    public function leave(): void
    {
        /** @var Context $context */
        $context = Container::getInstance()->get(Context::class);

        $currentSceneName = $context->get('state.session.$scene');

        if (is_string($currentSceneName) && $currentSceneName !== '' && isset($this->scenes[$currentSceneName])) {
            $this->scenes[$currentSceneName]->leave();
        }

        $context->set('state.session.$scene', null);
    }
}
