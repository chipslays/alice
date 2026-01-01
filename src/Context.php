<?php

namespace Alice;

use Alice\Scenes\Stage;
use Alice\Support\Collection;
use Alice\Support\Container;

class Context extends Collection
{
    public function enter(string $sceneName): void
    {
        Container::getInstance()->get(Stage::class)->enter($this, $sceneName);
    }

    public function leave(): void
    {
        Container::getInstance()->get(Stage::class)->leave($this);
    }
}
