<?php

namespace Alice;

use Alice\Settings;
use Alice\Events\Dispatcher;
use Alice\Support\Container;
use Alice\Traits\Eventable;

class Alice
{
    use Eventable;

    public readonly Container $container;

    protected ?Context $fakeContext = null;

    public function __construct(
        public readonly Settings $settings = new Settings
    ) {
        $this->container = new Container;
        $this->container->instance(self::class, $this);
    }

    public function fake(string $json): void
    {
        $this->fakeContext = new Context(json_decode($json, true));
    }

    public function dispatch(): void
    {
        $context = $this->fakeContext !== null
            ? $this->fakeContext
            : $this->captureRequest();

        $this->container->instance(Context::class, $context);

        $this->getEventDispatcher()->dispatch($context);
    }

    protected function captureRequest(): Context
    {
        $input = file_get_contents('php://input');
        return new Context(json_decode($input, true) ?? []);
    }

    protected function getEventDispatcher(): Dispatcher
    {
        return $this->eventDispatcher ??= new Dispatcher($this->container);
    }
}
