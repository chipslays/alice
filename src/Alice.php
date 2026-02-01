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
     * Слой сцен для регистрации и выполнения логики диалога.
     *
     * @var Stage
     */
    protected Stage $stage;

    /**
     * Создает экземпляр Alice и регистрирует основные сервисы в контейнере.
     *
     * @param Settings $settings Настройки приложения
     */
    public function __construct(
        public readonly Settings $settings = new Settings
    ) {
        $container = Container::getInstance();
        $container->instance(self::class, $this);
        $container->instance(Settings::class, $settings);
        $container->instance(Stage::class, $this->stage = new Stage);
    }

    /**
     * Устанавливает фейковый контекст из JSON (для тестирования).
     *
     * @param string $json JSON-строка с контекстом запроса
     * @return void
     */
    public function fake(string $json): void
    {
        $this->fakeContext = new Context((array) (json_decode($json, true) ?? []));
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
        $context = $this->resolveContext();

        $container = Container::getInstance();

        /** @var array<string,mixed> $interfaces */
        $interfaces = (array) $context->get('meta.interfaces', []);
        $container->instance(Interfaces::class, new Interfaces($interfaces));

        /** @var array<'tokens'|int,mixed> $tokensData */
        $tokensData = (array) $context->get('request.nlu.tokens', []);
        $container->instance(Tokens::class, new Tokens($tokensData));

        /** @var array<int,mixed> $entitiesData */
        $entitiesData = (array) $context->get('request.nlu.entities', []);
        $container->instance(Entities::class, new Entities($entitiesData));

        /** @var array<int,mixed> $intentsData */
        $intentsData = (array) $context->get('request.nlu.intents', []);
        $container->instance(Intents::class, new Intents($intentsData));

        $this->bindServices($context);

        try {
            $handledByScene = $this->stage->dispatch();

            if (!$handledByScene) {
                $this->getEventDispatcher()->dispatch();
            }
        } catch (Throwable $th) {
            // Если зарегистрирован обработчик ошибок — вызываем его
            if ($this->errorHandler) {
                call_user_func($this->errorHandler, $context, $th);
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
     * @return Context
     */
    protected function resolveContext(): Context
    {
        return $this->fakeContext ?? $this->captureRequest();
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
     * @return Context
     */
    protected function captureRequest(): Context
    {
        $input = file_get_contents('php://input');
        $decoded = json_decode((string) $input, true);
        return new Context((array) ($decoded ?? []));
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
     * @param array<int,mixed>|string    $buttons Массив или строка с кнопками
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
    public function replyWith(AbstractCard|AudioPlayer $type, string $text = '', ?string $tts = null, bool $finish = false): void
    {
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
}
