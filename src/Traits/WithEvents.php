<?php

namespace Alice\Traits;

use Alice\Context;
use Alice\Events\Dispatcher;
use Alice\Events\Event;
use Alice\Events\Group;
use Closure;

/**
 * Трейт, предоставляющий синтаксический сахар для регистрации событий и middleware.
 */
trait WithEvents
{
    protected ?Dispatcher $eventDispatcher = null;

    // Свойство для хранения обработчика ошибок
    protected Closure|array|string|null $errorHandler = null;

    /**
     * Регистрирует обработчик события с правилами.
     *
     * @param Closure|array<int|string,mixed>|string $rules
     * @param Closure|array<int,mixed>|string $handler
     * @return Event
     */
    public function on(Closure|array|string $rules, Closure|array|string $handler): Event
    {
        return $this->getEventDispatcher()->add($this, $rules, $handler);
    }

    /**
     * Группирует регистрацию событий в рамках callback.
     *
     * @param Closure $callback
     * @return Group
     */
    public function group(Closure $callback): Group
    {
        return $this->getEventDispatcher()->group(function() use ($callback) {
            $callback($this);
        });
    }

    /**
     * Регистрирует middleware для диспетчера событий.
     *
     * @param array<int, string|Closure|array<int,mixed>>|string|Closure $middleware Middleware или список middleware
     * @return self
     */
    public function middleware(string|array|Closure $middleware): self
    {
        $this->getEventDispatcher()->pipe($middleware);

        return $this;
    }

    /**
     * Устанавливает обработчик ошибок, вызываемый при исключениях.
     *
     * @param Closure|array|string $handler Обработчик ошибок
     * @return self
     */
    public function onError(Closure|array|string $handler): self
    {
        $this->errorHandler = $handler;

        return $this;
    }

    /**
     * Регистрирует обработчик по умолчанию (fallback). Вызывается, если не найдено
     * других обработчиков.
     *
     * @param Closure|array<int,mixed>|string $handler Обработчик по умолчанию
     * @return Event
     */
    public function onFallback(Closure|array|string $handler): Event
    {
        return $this->on(function() { return true; }, $handler)->priority(Event::PRIORITY_LOWEST);
    }

    abstract protected function getEventDispatcher(): Dispatcher;

    /**
     * Регистрирует обработчик события старта новой сессии.
     *
     * @param Closure|array<int,mixed>|string $handler Обработчик старта
     * @return Event
     */
    public function onStart(Closure|array|string $handler): Event
    {
        $rule = function (Context $context): bool {
            return
                $context->get('session.new') &&

                // только если команда пустая,
                // чтобы не пропустить запрос вида: спроси у <навыка> что-нибудь
                in_array($context->get('request.command'), [null, ''], strict: true);
        };

        return $this->on($rule, $handler)->priority(Event::PRIORITY_HIGHEST);
    }

    /**
     * Регистрирует обработчик для конкретной команды текста запроса.
     *
     * @param array<int,string>|string $command Команда или список команд
     * @param Closure|array<int,mixed>|string $handler Обработчик
     * @return Event
     */
    public function onCommand(array|string $command, Closure|array|string $handler): Event
    {
        return $this->on(['request.command' => $command], $handler);
    }

    /**
     * Регистрирует обработчик для оригинального текста запроса.
     *
     * @param array<int,string>|string $originalUtterance
     * @param Closure|array<int,mixed>|string $handler Обработчик
     * @return Event
     */
    public function onOriginalUtterance(array|string $originalUtterance, Closure|array|string $handler): Event
    {
        return $this->on(['request.original_utterance' => $originalUtterance], $handler);
    }

    /**
     * Регистрирует обработчик для токенов (слов).
     *
     * Сработает если хотя бы 1 токен есть в запросе.
     *
     * @param array|string $token
     * @param Closure|array|string $handler
     * @return Event
     */
    public function onNluTokenAny(array|string $token, Closure|array|string $handler): Event
    {
        return $this->on(function (Context $context) use ($token): bool {
            $tokens = $context->get('request.nlu.tokens', []);
            $needles = is_array($token) ? $token : [$token];

            foreach ($needles as $needle) {
                if (in_array($needle, $tokens, true)) {
                    return true;
                }
            }
            return false;
        }, $handler);
    }

    /**
     * Регистрирует обработчик для токенов (слов).
     *
     * Сработает если все переданные токены есть в запросе.
     *
     * @param array $token
     * @param Closure|array|string $handler
     * @return Event
     */
    public function onNluToken(array $token, Closure|array|string $handler): Event
    {
        return $this->on(function (Context $context) use ($token): bool {
            $tokens = $context->get('request.nlu.tokens', []);

            foreach ($token as $needle) {
                if (!in_array($needle, $tokens, true)) {
                    return false;
                }
            }
            return true;
        }, $handler);
    }

    /**
     * Регистрирует обработчик для action-пэйлоада.
     *
     * @param array<int,string>|string $action Идентификатор action или список
     * @param Closure|array<int,mixed>|string $handler Обработчик
     * @return Event
     */
    public function onAction(array|string $action, Closure|array|string $handler): Event
    {
        return $this->on(['request.payload.$action' => $action], $handler);
    }

    /**
     * Регистрирует обработчик для любого типа запроса (request.type).
     *
     * @param Closure|array<int,mixed>|string $handler Обработчик
     * @return Event
     */
    public function onAny(Closure|array|string $handler): Event
    {
        return $this->on('request.type', $handler)->priority(Event::PRIORITY_HIGH + 200);
    }

    /**
     * Регистрирует обработчик для указанного интента NLU.
     *
     * @param array<int,string>|string $id Идентификатор интента или список идентификаторов
     * @param Closure|array<int,mixed>|string $handler Обработчик
     * @return Event
     */
    public function onIntent(array|string $id, Closure|array|string $handler): Event
    {
        $rule = function (Context $context) use ($id): bool {
            return (bool) array_intersect((array) $id, array_keys((array) $context->get('request.nlu.intents')));
        };

        return $this->on($rule, $handler);
    }

    /**
     * Регистрирует обработчик для интента подтверждения (YANDEX.CONFIRM).
     *
     * @param Closure|array<int,mixed>|string $handler Обработчик
     * @return Event
     */
    public function onConfirm(Closure|array|string $handler): Event
    {
        return $this->onIntent('YANDEX.CONFIRM', $handler);
    }

    /**
     * Регистрирует обработчик для интента отказа (YANDEX.REJECT).
     *
     * @param Closure|array<int,mixed>|string $handler Обработчик
     * @return Event
     */
    public function onReject(Closure|array|string $handler): Event
    {
        return $this->onIntent('YANDEX.REJECT', $handler);
    }

    /**
     * Регистрирует обработчик для интента помощи (YANDEX.HELP).
     *
     * @param Closure|array<int,mixed>|string $handler Обработчик
     * @return Event
     */
    public function onHelp(Closure|array|string $handler): Event
    {
        return $this->onIntent('YANDEX.HELP', $handler);
    }

    /**
     * Регистрирует обработчик для интента повтора (YANDEX.REPEAT).
     *
     * @param Closure|array<int,mixed>|string $handler Обработчик
     * @return Event
     */
    public function onRepeat(Closure|array|string $handler): Event
    {
        return $this
            ->onIntent('YANDEX.REPEAT', $handler)
            ->priority(Event::PRIORITY_HIGH + 100)
            ->middleware(function(Context $context, callable $next) {
                // Говорим, что запрос на повтор мы обработаем сами,
                // а не автоматически отправим предыдущий ответ
                $context->repeatShouldBeHandledManually = true;
                return $next($context);
            });
    }

    /**
     * Регистрирует обработчик для интента "что ты умеешь" (YANDEX.WHAT_CAN_YOU_DO).
     *
     * @param Closure|array<int,mixed>|string $handler Обработчик
     * @return Event
     */
    public function onWhatCanYouDo(Closure|array|string $handler): Event
    {
        return $this->onIntent('YANDEX.WHAT_CAN_YOU_DO', $handler)->priority(Event::PRIORITY_HIGH);
    }

    /**
     * Регистрирует обработчик для опасного контекста (dangerous_context).
     *
     * @param Closure|array<int,mixed>|string $handler Обработчик
     * @return Event
     */
    public function onDangerous(Closure|array|string $handler): Event
    {
        return $this->on(['request.markup.dangerous_context' => true], $handler)->priority(Event::PRIORITY_HIGHEST + 100);
    }

    /**
     * Регистрирует обработчик подтверждения покупки.
     *
     * @param Closure|array<int,mixed>|string $handler Обработчик
     * @return Event
     */
    public function onPurchaseConfirmation(Closure|array|string $handler): Event
    {
        return $this->on(['request.type' => 'Purchase.Confirmation'], $handler);
    }

    /**
     * Регистрирует обработчик события Show.Pull.
     *
     * @param Closure|array<int,mixed>|string $handler Обработчик
     * @return Event
     */
    public function onShowPull(Closure|array|string $handler): Event
    {
        return $this->on(['request.type' => 'Show.Pull'], $handler);
    }

    /**
     * Регистрирует обработчик события начала воспроизведения AudioPlayer.
     *
     * @param Closure|array<int,mixed>|string $handler Обработчик
     * @return Event
     */
    public function onAudioPlayerPlaybackStarted(Closure|array|string $handler): Event
    {
        return $this->on(['request.type' => 'AudioPlayer.PlaybackStarted'], $handler);
    }

    /**
     * Регистрирует обработчик события завершения воспроизведения AudioPlayer.
     *
     * @param Closure|array<int,mixed>|string $handler Обработчик
     * @return Event
     */
    public function onAudioPlayerPlaybackFinished(Closure|array|string $handler): Event
    {
        return $this->on(['request.type' => 'AudioPlayer.PlaybackFinished'], $handler);
    }

    /**
     * Регистрирует обработчик события почти завершенного воспроизведения AudioPlayer.
     *
     * @param Closure|array<int,mixed>|string $handler Обработчик
     * @return Event
     */
    public function onAudioPlayerPlaybackNearlyFinished(Closure|array|string $handler): Event
    {
        return $this->on(['request.type' => 'AudioPlayer.PlaybackNearlyFinished'], $handler);
    }

    /**
     * Регистрирует обработчик остановки воспроизведения AudioPlayer.
     *
     * @param Closure|array<int,mixed>|string $handler Обработчик
     * @return Event
     */
    public function onAudioPlayerPlaybackStopped(Closure|array|string $handler): Event
    {
        return $this->on(['request.type' => 'AudioPlayer.PlaybackStopped'], $handler);
    }

    /**
     * Регистрирует обработчик ошибки воспроизведения AudioPlayer.
     *
     * @param Closure|array<int,mixed>|string $handler Обработчик
     * @return Event
     */
    public function onAudioPlayerPlaybackFailed(Closure|array|string $handler): Event
    {
        return $this->on(['request.type' => 'AudioPlayer.PlaybackFailed'], $handler);
    }
}
