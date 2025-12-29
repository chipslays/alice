<?php

namespace Alice\Events;

use Alice\Support\Container;
use Alice\Support\Pipeline;
use Alice\Context;
use Closure;

class Dispatcher
{
    /** @var Event[] */
    protected array $listeners = [];

    /** @var array */
    protected array $groupStack = [];

    /** @var array */
    protected array $globalMiddleware = [];

    public function __construct(
        protected Container $container
    ) {}

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

    public function add(array|string $rules, Closure|callable|array|string $handler): Event
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

    public function dispatch(Context $context): void
    {
        $pipeline = new Pipeline($this->container);

        foreach ($this->listeners as $event) {
            if ($this->matches($event->rules, $context)) {

                $middlewares = array_merge($this->globalMiddleware, $event->middleware);

                $destination = function ($context) use ($event) {
                    // 1. Достаем параметры, которые сохранили в matchSmartString
                    $routeParams = $context->get('matches') ?? [];

                    // 2. Передаем их в call!
                    // Контейнер увидит ['command' => 'value'] и подставит в $command
                    return $this->container->call($event->handler, $routeParams);
                };

                $pipeline->send($context, $middlewares, $destination);
            }
        }
    }


    // --- НОВАЯ МОЩНАЯ ЛОГИКА MATCHES ---

    private function matches(array|string|Closure $rules, Context $context): bool
    {
        // 1. Нормализация (если передана просто строка или клоужер — заворачиваем в массив)
        if ($rules instanceof Closure || is_string($rules)) {
            $rules = [$rules];
        }

        // 2. Проходим по всем правилам.
        // Логика AND: Все условия в массиве должны быть выполнены.
        foreach ($rules as $key => $pattern) {

            // Если ключ — число, значит это правило "Проверь наличие ключа"
            // ['request.command']
            if (is_int($key)) {
                if (!$context->has($pattern)) {
                    return false;
                }
                continue;
            }

            // Иначе это проверка значения по ключу
            // ['request.command' => '/start/']

            // 3. Обработка Closure-правила
            if ($pattern instanceof Closure) {
                // Если замыкание вернуло false — правило не прошло
                if (!$pattern($context)) {
                    return false;
                }
                continue;
            }

            // Получаем значение из Context (subject)
            $subject = $context->get($key);

            // Если значения нет в Context — сразу нет
            if ($subject === null) {
                return false;
            }

            // 4. Проверяем паттерны (может быть массив вариантов для одного ключа - OR логика)
            $patterns = is_array($pattern) ? $pattern : [$pattern];
            $matched = false;

            foreach ($patterns as $p) {
                // Строгое равенство
                if ($p === $subject) {
                    $matched = true;
                    break;
                }

                // Smart String (фигурные скобки)
                if ($this->matchSmartString($p, $subject, $context)) {
                    $matched = true;
                    break;
                }

                // Regex
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

    /**
     * Проверка на регулярное выражение.
     * Если совпало — сохраняет группы захвата в Context!
     */
    private function matchRegex(string $pattern, string $subject, Context $context): bool
    {
        // Эвристика: если строка похожа на регулярку (начинается с / или ~)
        if (!str_starts_with($pattern, '/') && !str_starts_with($pattern, '~')) {
            return false;
        }

        if (@preg_match($pattern, $subject, $matches)) {
            // Сохраняем найденные аргументы в context, чтобы использовать в контроллере
            // $context->get('matches')
            $context->set('matches', array_slice($matches, 1));
            return true;
        }

        return false;
    }

    /**
     * Проверка "Умной строки" из твоего примера (cmd {arg}).
     */
    private function matchSmartString(string $pattern, string $subject, Context $context): bool
    {
        if (!str_contains($pattern, '{')) {
            return false;
        }

        $regex = $this->createPatternFromString($pattern);

        if (@preg_match($regex, $subject, $matches)) {
            // ВАЖНО: Убираем цифровые ключи (0, 1...), оставляем только именованные ('command')
            $params = array_filter($matches, fn($key) => !is_int($key), ARRAY_FILTER_USE_KEY);

            $context->set('matches', $params);

            return true;
        }

        return false;
    }


    /**
     * Конвертация 'command {arg}' -> Regex (из твоего примера)
     */
    private function createPatternFromString(string $value): string
    {
        // 1. Необязательные параметры {name?}
        $pattern = preg_replace_callback('~\s{(\w+)\?}~', function($m) {
            return '(?: (?P<' . $m[1] . '>.*?))?';
        }, $value);

        // 2. Обязательные параметры {name}
        return '~^' . preg_replace_callback('/{(\w+)}/', function($m) {
            return '(?P<' . $m[1] . '>.*?)';
        }, $pattern) . '$~u';
    }
}
