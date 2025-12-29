<?php

namespace Alice\Support;

use Alice\Context;
use Closure;

class Pipeline
{
    public function __construct(
        protected Container $container
    ) {}

    public function send(Context $context, array $middlewares, Closure $destination): mixed
    {
        // Создаем замыкание, которое будет передаваться как $next
        $pipeline = array_reduce(
            array_reverse($middlewares),
            function ($next, $middleware) {
                return function ($context) use ($next, $middleware) {
                    // Разрешаем middleware через контейнер
                    if (is_string($middleware)) {
                        $instance = $this->container->get($middleware);
                        return $instance->handle($context, $next);
                    }

                    // Если это просто замыкание function($context, $next)
                    if ($middleware instanceof Closure) {
                         return $middleware($context, $next);
                    }

                    // Если передан объект
                    return $middleware->handle($context, $next);
                };
            },
            $destination
        );

        return $pipeline($context);
    }
}
