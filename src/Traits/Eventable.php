<?php

namespace Alice\Traits;

use Alice\Events\Dispatcher;
use Alice\Events\Event;
use Alice\Events\Group;
use Closure;

trait Eventable
{
    protected ?Dispatcher $eventDispatcher = null;

    public function on(array|string $rules, Closure|callable|array|string $handler): Event
    {
        return $this->getEventDispatcher()->add($rules, $handler);
    }

    public function group(Closure $callback): Group
    {
        return $this->getEventDispatcher()->group(function() use ($callback) {
            $callback($this);
        });
    }

    public function middleware(string|array|Closure $middleware): self
    {
        $this->getEventDispatcher()->pipe($middleware);

        return $this;
    }

    abstract protected function getEventDispatcher(): Dispatcher;
}
