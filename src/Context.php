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
class Context extends Collection
{
    /**
     * Возвращает объект состояния приложения (Application).
     *
     * @return Application
     */
    public function application(): Application
    {
        return Container::getInstance()->get(Application::class);
    }

    /**
     * Возвращает объект сессии текущего пользователя (Session).
     *
     * @return Session
     */
    public function session(): Session
    {
        return Container::getInstance()->get(Session::class);
    }

    /**
     * Возвращает объект пользователя текущей сессии (User).
     *
     * @return User
     */
    public function user(): User
    {
        return Container::getInstance()->get(User::class);
    }

    /**
     * Возвращает доступные интерфейсы устройства (Interfaces).
     *
     * @return Interfaces
     */
    public function interfaces(): Interfaces
    {
        return Container::getInstance()->get(Interfaces::class);
    }

    /**
     * Возвращает токены NLU (Tokens) для текущего запроса.
     *
     * @return Tokens
     */
    public function tokens(): Tokens
    {
        return Container::getInstance()->get(Tokens::class);
    }

    /**
     * Возвращает распознанные сущности NLU (Entities) для текущего запроса.
     *
     * @return Entities
     */
    public function entities(): Entities
    {
        return Container::getInstance()->get(Entities::class);
    }

    /**
     * Возвращает распознанные интенты NLU (Intents) для текущего запроса.
     *
     * @return Intents
     */
    public function intents(): Intents
    {
        return Container::getInstance()->get(Intents::class);
    }

    /**
     * Войти в сцену по имени (делегирует Stage::enter).
     *
     * @param string $sceneName Имя сцены
     * @return void
     */
    public function enter(string $sceneName): void
    {
        Container::getInstance()->get(Stage::class)->enter($this, $sceneName);
    }

    /**
     * Выйти из текущей сцены (делегирует Stage::leave).
     *
     * @return void
     */
    public function leave(): void
    {
        Container::getInstance()->get(Stage::class)->leave($this);
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
