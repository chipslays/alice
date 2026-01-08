<?php

declare(strict_types=1);

namespace Alice\Events;

use Alice\Support\Pipeline;
use Alice\Context;
use Alice\Support\Container;
use Closure;

class Dispatcher
{
    /** @var Event[] */
    protected array $listeners = [];

    /** @var array */
    protected array $groupStack = [];

    /** @var array */
    protected array $globalMiddleware = [];

    public function pipe(string|Closure|array $middleware): self
    {
        $middlewares = is_array($middleware) ? $middleware : [$middleware];
        $this->globalMiddleware = array_merge($this->globalMiddleware, $middlewares);
        return $this;
    }

    public function group(Closure $callback): Group
    {
        $startIndex = count($this->listeners);
        $callback($this);
        $newEvents = array_slice($this->listeners, $startIndex);
        return new Group($newEvents);
    }

    public function add(Closure|array|string $rules, Closure|callable|array|string $handler): Event
    {
        $event = new Event($rules, $handler);
        foreach ($this->groupStack as $group) {
            if (isset($group['middleware'])) {
                $event->middleware($group['middleware']);
            }
        }
        $this->listeners[] = $event;
        return $event;
    }

    public function dispatch(): bool
    {
        $pipeline = new Pipeline;
        $context = Container::getInstance()->get(Context::class);
        $handled = false; // Флаг обработки

        foreach ($this->listeners as $event) {
            if ($this->matches($event->rules, $context)) {
                $middlewares = array_merge($this->globalMiddleware, $event->middleware);

                $destination = function ($context) use ($event) {
                    $routeParams = $context->get('matches') ?? [];
                    return Container::getInstance()->call($event->handler, $routeParams);
                };

                $pipeline
                    ->send($context)
                    ->through($middlewares)
                    ->then($destination);

                $handled = true;
                break;
            }
        }

        return $handled;
    }

    // --- ОБНОВЛЕННАЯ ЛОГИКА MATCHES ---

    private function matches(array|string|Closure $rules, Context $context): bool
    {
        // 1. Если передано Замыкание (Closure) — это кастомная проверка
        if ($rules instanceof Closure) {
            // Вызываем замыкание, передавая Context.
            // Ожидаем, что оно вернет true/false.
            return (bool) $rules($context);
        }

        // 2. Если строка — превращаем в массив для унификации
        if (is_string($rules)) {
            $rules = [$rules];
        }

        // 3. Стандартная проверка массива правил
        foreach ($rules as $key => $pattern) {

            // Если ключ — число, значит это правило "Проверь наличие ключа"
            if (is_int($key)) {
                // Если значение правила само является Closure (внутри массива правил)
                if ($pattern instanceof Closure) {
                    if (!$pattern($context)) {
                        return false;
                    }
                    continue;
                }

                // Иначе проверяем наличие ключа в контексте
                if (!$context->has($pattern)) {
                    return false;
                }
                continue;
            }

            // Проверка по ключу и значению
            $subject = $context->get($key);

            if ($subject === null) {
                return false;
            }

            $patterns = is_array($pattern) ? $pattern : [$pattern];
            $matched = false;

            foreach ($patterns as $p) {
                if ($p === $subject) {
                    $matched = true;
                    break;
                }
                if ($this->matchSmartString($p, $subject, $context)) {
                    $matched = true;
                    break;
                }
                if ($this->matchRegex($p, $subject, $context)) {
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                return false;
            }
        }

        return true;
    }

    private function matchRegex(string $pattern, string $subject, Context $context): bool
    {
        if (!str_starts_with($pattern, '/') && !str_starts_with($pattern, '~')) {
            return false;
        }

        if (@preg_match($pattern, $subject, $matches)) {
            $context->set('matches', array_slice($matches, 1));
            return true;
        }

        return false;
    }

    private function matchSmartString(string $pattern, string $subject, Context $context): bool
    {
        if (!str_contains($pattern, '{')) {
            return false;
        }

        $regex = $this->createPatternFromString($pattern);

        if (@preg_match($regex, $subject, $matches)) {
            $params = array_filter($matches, fn($key) => !is_int($key), ARRAY_FILTER_USE_KEY);
            $context->set('matches', $params);
            return true;
        }

        return false;
    }

    private function createPatternFromString(string $value): string
    {
        $pattern = preg_replace_callback('~\s{(\w+)\?}~', function($m) {
            return '(?: (?P<' . $m[1] . '>.*?))?';
        }, $value);

        return '~^' . preg_replace_callback('/{(\w+)}/', function($m) {
            return '(?P<' . $m[1] . '>.*?)';
        }, $pattern) . '$~u';
    }
}
