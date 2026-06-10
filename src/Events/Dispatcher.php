<?php

declare(strict_types=1);

namespace Alice\Events;

use Alice\Support\Pipeline;
use Alice\Context;
use Alice\Settings;
use Alice\State\Session;
use Alice\Support\Container;
use Closure;

/**
 * Диспетчер событий с поддержкой middleware, групп и правил соответствия.
 */
class Dispatcher
{
    /** @var Event[] */
    protected array $listeners = [];

    /** @var array<int,mixed> */
    protected array $groupStack = [];

    /** @var array<int,mixed> */
    protected array $globalMiddleware = [];

    /**
     * Добавляет глобальный middleware.
     *
     * @param array<int,mixed>|string|Closure $middleware
     * @return self
     */
    public function pipe(string|Closure|array $middleware): self
    {
        $middlewares = is_array($middleware) ? $middleware : [$middleware];
        $this->globalMiddleware = array_merge($this->globalMiddleware, $middlewares);
        return $this;
    }

    /**
     * Группирует события, возвращая объект Group.
     *
     * @param Closure $callback
     * @return Group
     */
    public function group(Closure $callback): Group
    {
        $startIndex = count($this->listeners);
        $callback($this);
        $newEvents = array_slice($this->listeners, $startIndex);
        return new Group($newEvents);
    }

    /**
     * Регистрирует событие с правилами и обработчиком.
     *
     * @param Closure|array<int|string,mixed>|string $rules
     * @param Closure|array<int,mixed>|string $handler
     * @return Event
     */
    public function add(Closure|array|string $rules, Closure|array|string $handler): Event
    {
        $event = new Event($rules, $handler);

        foreach ($this->groupStack as $group) {
            /** @var array{middleware?: array<int, Closure|array<int,mixed>|string>|string|Closure} $group */
            if (isset($group['middleware'])) {
                $event->middleware($group['middleware']);
            }
        }

        $this->listeners[spl_object_id($event)] = $event;

        return $event;
    }

    /**
     * Обходит зарегистрированные события и выполняет первое подходящее через Pipeline.
     *
     * @return bool true, если событие обработано
     */
    public function dispatch(?int $eventId = null): bool
    {
        /** @var Context $context */
        $context = Container::getInstance()->get(Context::class);

        // Сортируем события по приоритету (от большего к меньшему)
        $sortedListeners = array_values($this->listeners);
        usort($sortedListeners, function(Event $a, Event $b) {
            return $b->priority <=> $a->priority;
        });

        if ($eventId !== null) {
            $event = $this->listeners[$eventId] ?? null;

            if ($event) {
                $this->fire($eventId, $event, $context);
                return true;
            }

            // Если не нашли событие, то продолжаем дальше по обычной логике
        }

        foreach ($sortedListeners as $id => $event) {
            if ($this->matches($event->rules, $context)) {
                $this->fire($id, $event, $context);

                return true;
            }
        }

        return false;
    }

    protected function fire(int $id, Event $event, Context $context): void
    {
        /** @var Settings $settings */
        $settings = Container::getInstance()->get(Settings::class);

        $globalMiddlewaresFromSettings = $settings->get('middlewares.global', []);

        /** @var array<int,(callable(): mixed)|object|string> $middlewares */
        $middlewares = array_merge($globalMiddlewaresFromSettings, $this->globalMiddleware, $event->middleware);

        // Достаем мидлвари из алиасов, если они есть в настройках
        $middlewareAliases = $settings->get('middlewares.aliases', []);
        foreach ($middlewares as $key => $middleware) {
            if (is_string($middleware) && isset($middlewareAliases[$middleware])) {
                $middlewares[$key] = $middlewareAliases[$middleware];
            }
        }

        $destination = function (Context $context) use ($id, $event) {
            $container = Container::getInstance();

            // Сохраняем ID текущего ответа в сессии,
            // только если это не запрос на повтор
            if ($context->shouldRepeatPreviousRequest() && !$context->repeatShouldBeHandledManually) {
                /** @var Session */
                $session = $container->get(Session::class);
                $session->set('$repeat', $id);
            }

            $parameters = $context->get('matches') ?? [];

            return $container->call($event->handler, $parameters);
        };

        (new Pipeline)
            ->send($context)
            ->through($middlewares)
            ->then($destination);
    }

    /**
     * @param Closure|array<int|string,mixed>|string $rules
     */
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
                if (!is_string($pattern) && !is_int($pattern)) {
                    return false;
                }

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

                if (!is_string($p)) {
                    continue;
                }

                if (!is_string($subject) && !is_numeric($subject)) {
                    continue;
                }

                $subjectStr = (string) $subject;

                if ($this->matchSmartString($p, $subjectStr, $context)) {
                    $matched = true;
                    break;
                }
                if ($this->matchRegex($p, $subjectStr, $context)) {
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

        // Если preg_replace_callback вернул null — используем исходное значение
        $pattern = is_string($pattern) ? $pattern : $value;

        $replaced = preg_replace_callback('/{(\w+)}/', function($m) {
            return '(?P<' . $m[1] . '>.*?)';
        }, $pattern);

        $replaced = is_string($replaced) ? $replaced : $pattern;

        return '~^' . $replaced . '$~u';
    }
}
