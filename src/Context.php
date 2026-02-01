<?php

namespace Alice;

use Alice\Scenes\Stage;
use Alice\State\Application;
use Alice\State\Session;
use Alice\State\User;
use Alice\Support\Collection;
use Alice\Support\Container;
use Alice\Types\Meta\Interfaces;
use Alice\Types\Nlu\Entities\Entities;
use Alice\Types\Nlu\Intents\Intents;
use Alice\Types\Nlu\Tokens\Tokens;

/**
 * Обёртка контекста запроса с удобными методами доступа к состояниям и NLU.
 *
 * Расширяет Collection и предоставляет методы для получения application, session, user,
 * а также доступа к интерфейсам NLU (tokens, entities, intents).
 */
/**
 * @extends Collection<mixed>
 */
class Context extends Collection
{
    /**
     * Возвращает объект состояния приложения (Application).
     *
     * @return Application
     */
    public function application(): Application
    {
        /** @var Application $app */
        $app = Container::getInstance()->get(Application::class);
        return $app;
    }

    /**
     * Возвращает объект сессии текущего пользователя (Session).
     *
     * @return Session
     */
    public function session(): Session
    {
        /** @var Session $session */
        $session = Container::getInstance()->get(Session::class);
        return $session;
    }

    /**
     * Возвращает объект пользователя текущей сессии (User).
     *
     * @return User
     */
    public function user(): User
    {
        /** @var User $user */
        $user = Container::getInstance()->get(User::class);
        return $user;
    }

    /**
     * Возвращает доступные интерфейсы устройства (Interfaces).
     *
     * @return Interfaces
     */
    public function interfaces(): Interfaces
    {
        /** @var Interfaces $interfaces */
        $interfaces = Container::getInstance()->get(Interfaces::class);
        return $interfaces;
    }

    /**
     * Возвращает токены NLU (Tokens) для текущего запроса.
     *
     * @return Tokens
     */
    public function tokens(): Tokens
    {
        /** @var Tokens $tokens */
        $tokens = Container::getInstance()->get(Tokens::class);
        return $tokens;
    }

    /**
     * Возвращает распознанные сущности NLU (Entities) для текущего запроса.
     *
     * @return Entities
     */
    public function entities(): Entities
    {
        /** @var Entities $entities */
        $entities = Container::getInstance()->get(Entities::class);
        return $entities;
    }

    /**
     * Возвращает распознанные интенты NLU (Intents) для текущего запроса.
     *
     * @return Intents
     */
    public function intents(): Intents
    {
        /** @var Intents $intents */
        $intents = Container::getInstance()->get(Intents::class);
        return $intents;
    }

    /**
     * Войти в сцену по имени (делегирует Stage::enter).
     *
     * @param string $sceneName Имя сцены
     * @return void
     */
    public function enter(string $sceneName): void
    {
        /** @var Stage $stage */
        $stage = Container::getInstance()->get(Stage::class);
        $stage->enter($sceneName);
    }

    /**
     * Выйти из текущей сцены (делегирует Stage::leave).
     *
     * @return void
     */
    public function leave(): void
    {
        /** @var Stage $stage */
        $stage = Container::getInstance()->get(Stage::class);
        $stage->leave();
    }

    /**
     * Проверяет, является ли сессия новой.
     *
     * @return bool
     */
    public function isNewSession(): bool
    {
        return $this->get('session.new') === true;
    }

    /**
     * Проверяет, авторизован ли пользователь в сессии.
     *
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        return $this->get('session.user.user_id') !== null;
    }
}
