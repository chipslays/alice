<?php

namespace Alice;

use Alice\Settings;
use Alice\Events\Dispatcher;
use Alice\Scenes\Stage;
use Alice\State;
use Alice\Support\Container;
use Alice\Traits\Eventable;
use Alice\Support\Defer;
use Alice\Support\Render;
use Alice\Types\Card\AbstractCard;
use Alice\Types\Directives\AudioPlayer\AudioPlayer;
use Alice\Types\Meta\Interfaces;
use Alice\Types\Nlu\Entities\Entities;
use Alice\Types\Nlu\Intents\Intents;
use Alice\Types\Nlu\Tokens\Tokens;
use Closure;
use JsonException;
use RuntimeException;
use Throwable;

/**
 * Main Alice application orchestrator.
 *
 * Отвечает за разрешение контекста, регистрацию сцен и запуск обработки запроса.
 */
class Alice
{
    use Eventable;

    /**
     * Фейковый контекст, используемый в тестах.
     *
     * @var Context|null
     */
    protected ?Context $fakeContext = null;

    /**
     * Реальный контекст запроса.
     *
     * @var Context|null
     */
    protected ?Context $realContext = null;

    /**
     * Слой сцен для регистрации и выполнения логики диалога.
     *
     * @var Stage
     */
    public readonly Stage $stage;

    /**
     * Текущий контекст запроса.
     * Доступен сразу после создания экземпляра Alice.
     *
     * @var Context|null
     */
    public protected(set) ?Context $context = null;

    /**
     * Создает экземпляр Alice и регистрирует основные сервисы в контейнере.
     * Сразу инициализирует контекст запроса.
     *
     * @param Settings $settings Настройки приложения
     */
    public function __construct(
        public readonly Settings $settings = new Settings,
    ) {
        $this->initializeContext();

        $container = Container::getInstance();

        $container->instance(Alice::class, $this);
        $container->instance(Settings::class, $settings);
        $container->instance(Stage::class, $this->stage = new Stage);
    }

    /**
     * Инициализирует контекст запроса (реальный или фейковый).
     *
     * @return void
     */
    protected function initializeContext(): void
    {
        $this->context = $this->resolveContext();

        // Сразу регистрируем контекст и основные сервисы в контейнере
        if ($this->context) {
            $this->bindServices($this->context);
        }
    }

    /**
     * Устанавливает фейковый контекст из JSON (для тестирования).
     * После установки фейкового контекста, он становится доступен через $alice->context.
     *
     * @param array<string|int, mixed>|string $data JSON-строка с контекстом запроса
     * @return static
     */
    public function fake(array|string $data): static
    {
        $decodedData = is_array($data) ? $data : (json_decode($data, true) ?? []);

        $this->fakeContext = new Context((array) $decodedData);

        $this->context = $this->fakeContext;

        $this->bindServices($this->context);

        return $this;
    }

    /**
     * Регистрирует обработчик сцены по имени.
     *
     * @param string  $name     Имя сцены
     * @param Closure $callback Колбэк, выполняемый при входе в сцену
     * @return void
     */
    public function onScene(string $name, Closure $callback): void
    {
        $this->stage->register($name, $callback);
    }

    /**
     * Обрабатывает входящий запрос: разрешает контекст, биндет сервисы и
     * выполняет сцену или диспатчер событий. Выполняет отложенные задачи в конце.
     *
     * @return void
     * @throws Throwable Если не зарегистрирован обработчик ошибок и возникло исключение
     */
    public function dispatch(): void
    {
        if (!$this->context) {
            // Пробуем инициализировать контекст ещё раз, возможно
            // был передан фейковый контекст для тестирования
            $this->initializeContext();

            if (!$this->context) {
                throw new RuntimeException('Контекст запроса не определен.');
            }
        }

        $container = Container::getInstance();

        /** @var array<string,mixed> $interfaces */
        $interfaces = (array) $this->context->get('meta.interfaces', []);
        $container->instance(Interfaces::class, new Interfaces($interfaces));

        /** @var array<'tokens'|int,mixed> $tokensData */
        $tokensData = (array) $this->context->get('request.nlu.tokens', []);
        $container->instance(Tokens::class, new Tokens($tokensData));

        /** @var array<int,mixed> $entitiesData */
        $entitiesData = (array) $this->context->get('request.nlu.entities', []);
        $container->instance(Entities::class, new Entities($entitiesData));

        /** @var array<int,mixed> $intentsData */
        $intentsData = (array) $this->context->get('request.nlu.intents', []);
        $container->instance(Intents::class, new Intents($intentsData));

        try {
            if ($this->context->isPing()) {
                $this->reply('pong', finish: true);
            } else {
                // Проверяем нужно ли повторить предыдущий запрос,
                // и если да — передаем ID последнего ответа
                // в диспатчер сцены и событий
                $eventId = null;
                if ($this->context->shouldRepeatPreviousRequest() && !$this->context->repeatShouldBeHandledManually) {
                    $replyValue = $this->context->get('state.session.$reply');
                    $eventId = is_int($replyValue) ? $replyValue : null;
                }

                $handledByScene = $this->stage->dispatch($eventId);

                if (!$handledByScene) {
                    $this->getEventDispatcher()->dispatch($eventId);
                }
            }
        } catch (Throwable $th) {
            // Если зарегистрирован обработчик ошибок — вызываем его
            if ($this->errorHandler) {
                Container::getInstance()->call($this->errorHandler, [$this->context, $th]);
            } else {
                // Иначе выбрасываем исключение дальше
                throw $th;
            }
        }

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        Defer::run();
    }

    /**
     * Возвращает текущий контекст запроса; если установлен фейковый контекст — возвращает его.
     *
     * @return Context|null
     */
    protected function resolveContext(): ?Context
    {
        if ($this->fakeContext) {
            return $this->fakeContext;
        }

        if ($this->realContext) {
            return $this->realContext;
        }

        return $this->realContext = $this->captureRequest();
    }

    /**
     * Регистрирует в контейнере контекст и объекты состояния (application, session, user).
     *
     * @param Context $context
     * @return void
     */
    protected function bindServices(Context $context): void
    {
        $container = Container::getInstance();

        $container->instance(Context::class, $context);

        $container->instance(
            State\Application::class,
            new State\Application((array) $context->get('state.application', []))
        );

        $container->instance(
            State\Session::class,
            new State\Session((array) $context->get('state.session', []))
        );

        $container->instance(
            State\User::class,
            new State\User((array) $context->get('state.user', []))
        );
    }

    /**
     * Читает сырой ввод из php://input и преобразует его в объект Context.
     *
     * @return Context|null
     */
    protected function captureRequest(): ?Context
    {
        $input = file_get_contents('php://input');

        if ($input === false) {
            return null;
        }

        try {
            $decoded = json_decode($input, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            return null;
        }

        if (!is_array($decoded)) {
            die('Всё хорошо, не переживай 👌');
        }

        return new Context($decoded);
    }

    /**
     * Возвращает экземпляр диспетчера событий, создавая его при необходимости.
     *
     * @return Dispatcher
     */
    protected function getEventDispatcher(): Dispatcher
    {
        // Передаем глобальный контейнер в диспетчер
        return $this->eventDispatcher ??= new Dispatcher;
    }

    /**
     * Отправляет текстовый ответ с опциональными кнопками и tts.
     *
     * @param string          $text    Основной текст ответа
     * @param string|null     $tts     TTS-версия текста (по умолчанию равна $text)
     * @param array<int,mixed>|string  $buttons Массив или строка с кнопками
     * @param bool            $finish  Завершать ли сессию после ответа
     * @return void
     */
    public function reply(string $text, ?string $tts = null, array|string $buttons = [], bool $finish = false): void
    {
        $processed = Render::process([
            'text' => $text,
            'tts' => $tts ?? $text,
        ]);

        echo (new Response)
            ->text($processed['text'])
            ->tts($processed['tts'])
            ->withButtons($buttons)
            ->finish($finish);
    }

    /**
     * Отправляет ответ с карточкой или AudioPlayer, с опциональным текстом и tts.
     *
     * @param AbstractCard|AudioPlayer $type   Карточка или плеер для ответа
     * @param string                   $text   Основной текст ответа
     * @param string|null              $tts    TTS-версия текста (по умолчанию равна $text)
     * @param bool                     $finish Завершать ли сессию после ответа
     * @return void
     */
    public function replyWith(
        AbstractCard|AudioPlayer $type,
        string $text = '',
        ?string $tts = null,
        bool $finish = false
    ): void {
        $processed = Render::process([
            'text' => $text,
            'tts' => $tts ?? $text,
        ]);

        $response = new Response;

        if ($type instanceof AbstractCard) {
            $response->withCard($type);
        }

        if ($type instanceof AudioPlayer) {
            $response->withAudioPlayer($type);
        }

        echo $response
            ->text($processed['text'])
            ->tts($processed['tts'])
            ->finish($finish);
    }

    /**
     * Запустить обработку в рамках Yandex Cloud Functions.
     *
     * Использовать вместо метода `dispatch()`.
     *
     * @return string
     */
    public function dispatchAsCloudFunction(): string
    {
        ob_start();

        $this->dispatch();

        $response = ob_get_contents();

        ob_end_clean();

        return $response !== false ? $response : '';
    }
}
