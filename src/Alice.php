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
use Closure;

class Alice
{
    use Eventable;

    protected ?Context $fakeContext = null;

    protected Stage $stage;

    public function __construct(
        public readonly Settings $settings = new Settings
    ) {
        $container = Container::getInstance();
        $container->instance(self::class, $this);
        $container->instance(Settings::class, $settings);

        $this->stage = new Stage($this->getEventDispatcher());
        $container->instance(Stage::class, $this->stage);
    }

    public function fake(string $json): void
    {
        $this->fakeContext = new Context(json_decode($json, true));
    }

    public function onScene(string $name, Closure $callback): void
    {
        $this->stage->register($name, $callback);
    }

    public function dispatch(): void
    {
        $context = $this->resolveContext();

        $this->bindServices($context);

        $handledByScene = $this->stage->dispatch();

        if (!$handledByScene) {
            $this->getEventDispatcher()->dispatch();
        }

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        Defer::run();
    }

    protected function resolveContext(): Context
    {
        return $this->fakeContext ?? $this->captureRequest();
    }

    protected function bindServices(Context $context): void
    {
        $container = Container::getInstance();

        $container->instance(Context::class, $context);

        $container->instance(
            State\Application::class,
            new State\Application($context->get('state.application', []))
        );

        $container->instance(
            State\Session::class,
            new State\Session($context->get('state.session', []))
        );

        $container->instance(
            State\User::class,
            new State\User($context->get('state.user', []))
        );
    }

    protected function captureRequest(): Context
    {
        $input = file_get_contents('php://input');
        return new Context(json_decode($input, true) ?? []);
    }

    protected function getEventDispatcher(): Dispatcher
    {
        // Передаем глобальный контейнер в диспетчер
        return $this->eventDispatcher ??= new Dispatcher(Container::getInstance());
    }

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
