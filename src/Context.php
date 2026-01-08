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

class Context extends Collection
{
    public function application(): Application
    {
        return Container::getInstance()->get(Application::class);
    }

    public function session(): Session
    {
        return Container::getInstance()->get(Session::class);
    }

    public function user(): User
    {
        return Container::getInstance()->get(User::class);
    }

    public function interfaces(): Interfaces
    {
        return Container::getInstance()->get(Interfaces::class);
    }

    public function tokens(): Tokens
    {
        return Container::getInstance()->get(Tokens::class);
    }

    public function entities(): Entities
    {
        return Container::getInstance()->get(Entities::class);
    }

    public function intents(): Intents
    {
        return Container::getInstance()->get(Intents::class);
    }

    public function enter(string $sceneName): void
    {
        Container::getInstance()->get(Stage::class)->enter($this, $sceneName);
    }

    public function leave(): void
    {
        Container::getInstance()->get(Stage::class)->leave($this);
    }

    public function isNewSession(): bool
    {
        return $this->get('session.new') === true;
    }

    public function isAuthenticated(): bool
    {
        return $this->get('session.user.user_id') !== null;
    }
}
