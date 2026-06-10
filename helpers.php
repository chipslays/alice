<?php

use Alice\Alice;
use Alice\Scenes\Stage;
use Alice\Support\Collection;
use Alice\Support\Container;
use Alice\Support\Defer;
use Alice\Types\Card\AbstractCard;
use Alice\Types\Directives\AudioPlayer\AudioPlayer;

if (!function_exists('container')) {
    /**
     * Возвращает контейнер зависимостей или конкретный сервис из контейнера.
     *
     * @param string|null $abstract Имя сервиса или абстракции для получения из контейнера.
     * @return mixed Если $abstract равен null — возвращается экземпляр контейнера (Container),
     *               иначе — запрошенный сервис.
     */
    function container(?string $abstract = null): mixed
    {
        if (is_null($abstract)) {
            return Container::getInstance();
        }

        return Container::getInstance()->get($abstract);
    }
}

if (!function_exists('call')) {
    /**
     * Вызывает обработчик через контейнер (с поддержкой автозаполнения зависимостей).
     *
     * @param callable|array<mixed>|string $handler Обработчик: функция, [класс, 'метод'] или строковое представление.
     * @param array<mixed> $parameters Параметры, передаваемые обработчику.
     * @return mixed Результат вызова обработчика.
     */
    function call(mixed $handler, array $parameters = []): mixed
    {
        return Container::getInstance()->call($handler, $parameters);
    }
}

if (!function_exists('defer')) {
    /**
     * Добавляет отложенное выполнение (defer) в очередь.
     *
     * @param callable|array<mixed>|string $callback Функция или описание обработчика для defer.
     * @param array<mixed> $arguments Аргументы для передачи в callback при выполнении.
     * @param int $priority Приоритет выполнения (больше — выше приоритет).
     * @return void
     */
    function defer(
        callable|string|array $callback,
        array $arguments = [],
        int $priority = Defer::PRIORITY_NORMAL
    ): void {
        Defer::add($callback, $arguments, $priority);
    }
}

if (!function_exists('collection')) {
    /**
     * Создаёт новый экземпляр `Collection` из массива.
     *
     * @param array<mixed> $items Элементы коллекции.
     * @return Collection<mixed> Новый объект коллекции.
     */
    function collection(array $items = []): Collection
    {
        return new Collection($items);
    }
}

if (!function_exists('plural')) {
    /**
     * Возвращает корректную форму слова в зависимости от указанного числа (русские формы).
     *
     * Пример формата: ['арбуз', 'арбуза', 'арбузов'] — [именительный, родительный (2-4), родительный (5+)].
     *
     * @param int|float $count Число для склонения.
     * @param array<string> $forms Массив из трёх форм слова: [единственное, родительный(2-4), родительный(5+)].
     * @param string|null $format Формат вывода (по умолчанию "%d %s"). Можно передать "%s" чтобы вернуть только слово.
     * @return string Отформатированная строка с числом и словом или только словом (в зависимости от $format).
     */
    function plural(int|float $count, array $forms, ?string $format = null): string
    {
        $n = abs((int) $count);

        $index = ($n % 10 == 1 && $n % 100 != 11)
            ? 0
            : ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20) ? 1 : 2);

        $word = $forms[$index];

        // Если формат не задан, возвращаем "число слово"
        if ($format === null) {
            return $count . ' ' . $word;
        }

        // Можно использовать кастомный формат, например: "%s" (только слово) или "<b>%d</b> %s"
        return sprintf($format, $count, $word);
    }
}

if (!function_exists('enter')) {
    function enter(string $sceneName): void
    {
        /** @var Stage */
        $stage = Container::getInstance()->get(Stage::class);
        $stage->enter($sceneName);
    }
}

if (!function_exists('leave')) {
    function leave(): void
    {
        /** @var Stage */
        $stage = Container::getInstance()->get(Stage::class);
        $stage->leave();
    }
}

if (!function_exists('alice')) {
    function alice(): Alice
    {
        return Container::getInstance()->get(Alice::class);
    }
}

if (!function_exists('reply')) {
    function reply(
        string $text,
        ?string $tts = null,
        array|string $buttons = [],
        bool $finish = false
    ): void {
        /** @var Alice */
        $alice = Container::getInstance()->get(Alice::class);

        $alice->reply($text, $tts, $buttons, $finish);
    }
}

if (!function_exists('replyWith')) {
    function replyWith(
        AbstractCard|AudioPlayer $type,
        string $text = '',
        ?string $tts = null,
        bool $finish = false
    ): void {
        /** @var Alice */
        $alice = Container::getInstance()->get(Alice::class);

        $alice->replyWith($type, $text, $tts, $finish);
    }
}
