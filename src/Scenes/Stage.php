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

    public function __construct(
        protected Dispatcher $dispatcher
    ) {}

    public function register(string $name, Closure $callback): void
    {
        $scene = new Scene($name, $this->dispatcher);
        $callback($scene);
        $this->scenes[$name] = $scene;
    }

    public function get(string $name): ?Scene
    {
        return $this->scenes[$name] ?? null;
    }

    // Основной метод обработки запроса
    public function dispatch(): bool
    {
        $context = Container::getInstance()->get(Context::class);

        // 1. Получаем имя текущей сцены из сессии
        $currentSceneName = $context->get('state.session.$scene');

        if (!$currentSceneName || !isset($this->scenes[$currentSceneName])) {
            return false; // Сцена не активна или не найдена
        }

        $scene = $this->scenes[$currentSceneName];

        // 2. Делегируем обработку событий диспетчеру сцены
        $scene->dispatch();

        return true; // Сцена обработала запрос
    }

    // Метод входа в сцену
    public function enter(Context $context, string $sceneName): void
    {
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
    public function leave(Context $context): void
    {
        $currentSceneName = $context->get('state.session.$scene');

        if ($currentSceneName && isset($this->scenes[$currentSceneName])) {
            $this->scenes[$currentSceneName]->leave($context);
        }

        $context->set('state.session.$scene', null);
    }
}
