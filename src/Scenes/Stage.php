<?php

namespace Alice\Scenes;

use Alice\Context;
use Alice\Events\Dispatcher;
use Alice\Support\Container;
use Closure;

class Stage
{
    /** @var array<string, Scene> */
    protected array $scenes = [];

    public function register(string $name, Closure $callback): void
    {
        $scene = new Scene($name);
        $callback($scene);
        $this->scenes[$name] = $scene;
    }

    public function get(string $name): ?Scene
    {
        return $this->scenes[$name] ?? null;
    }

    public function dispatch(): bool
    {
        $context = Container::getInstance()->get(Context::class);
        $currentSceneName = $context->get('state.session.$scene');

        if (!$currentSceneName || !isset($this->scenes[$currentSceneName])) {
            return false;
        }

        $scene = $this->scenes[$currentSceneName];

        return $scene->dispatch();
    }

    // Метод входа в сцену
    public function enter(string $sceneName): void
    {
        $context = Container::getInstance()->get(Context::class);

        $oldSceneName = $context->get('state.session.$scene');

        // Если уже были в какой-то сцене — вызываем onLeave
        if ($oldSceneName && isset($this->scenes[$oldSceneName])) {
            $this->scenes[$oldSceneName]->leave($context);
        }

        // Сохраняем новую сцену в сессию
        $context->set('state.session.$scene', $sceneName);

        // Вызываем onEnter новой сцены
        if (isset($this->scenes[$sceneName])) {
            $this->scenes[$sceneName]->enter($context);
        }
    }

    // Метод выхода
    public function leave(): void
    {
        $context = Container::getInstance()->get(Context::class);

        $currentSceneName = $context->get('state.session.$scene');

        if ($currentSceneName && isset($this->scenes[$currentSceneName])) {
            $this->scenes[$currentSceneName]->leave($context);
        }

        $context->set('state.session.$scene', null);
    }
}
