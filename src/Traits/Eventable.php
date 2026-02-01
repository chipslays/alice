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
trait Eventable
{
    protected ?Dispatcher $eventDispatcher = null;

    // Свойство для хранения обработчика ошибок
    protected ?Closure $errorHandler = null;

    /**
     * Регистрирует обработчик события с правилами.
     *
     * @param Closure|array|string $rules
     * @param Closure|callable|array|string $handler
     * @return Event
     */
    public function on(Closure|array|string $rules, Closure|callable|array|string $handler): Event
    {
        return $this->getEventDispatcher()->add($rules, $handler);
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
     * @param string|array|Closure $middleware Middleware или список middleware
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
     * @param Closure $handler Обработчик ошибок
     * @return self
     */
    public function onError(Closure $handler): self
    {
        $this->errorHandler = $handler;

        return $this;
    }

    /**
     * Регистрирует обработчик по умолчанию (fallback). Вызывается, если не найдено
     * других обработчиков.
     *
     * @param Closure|array|string $handler Обработчик по умолчанию
     * @return Event
     */
    public function onFallback(Closure|array|string $handler): Event
    {
        return $this->on(function() { return true; }, $handler);
    }

    abstract protected function getEventDispatcher(): Dispatcher;

    /**
     * Регистрирует обработчик события старта новой сессии.
     *
     * @param Closure|array|string $handler Обработчик старта
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

        return $this->on($rule, $handler);
    }

    /**
     * Регистрирует обработчик для конкретной команды текста запроса.
     *
     * @param array|string $command Команда или список команд
     * @param Closure|array|string $handler Обработчик
     * @return Event
     */
    public function onCommand(array|string $command, Closure|array|string $handler): Event
    {
        return $this->on(['request.command' => $command], $handler);
    }

    /**
     * Регистрирует обработчик для action-пэйлоада.
     *
     * @param array|string $action Идентификатор action или список
     * @param Closure|array|string $handler Обработчик
     * @return Event
     */
    public function onAction(array|string $action, Closure|array|string $handler): Event
    {
        return $this->on(['request.payload.$action' => $action], $handler);
    }

    /**
     * Регистрирует обработчик для любого типа запроса (request.type).
     *
     * @param Closure|array|string $handler Обработчик
     * @return Event
     */
    public function onAny(Closure|array|string $handler): Event
    {
        return $this->on('request.type', $handler);
    }

    /**
     * Регистрирует обработчик для указанного интента NLU.
     *
     * @param array|string $id Идентификатор интента или список идентификаторов
     * @param Closure|array|string $handler Обработчик
     * @return Event
     */
    public function onIntent(array|string $id, Closure|array|string $handler): Event
    {
        $rule = function (Context $context) use ($id): bool {
            return (bool) array_intersect((array) $id, array_keys(
                $context->get('request.nlu.intents')
            ));
        };

        return $this->on($rule, $handler);
    }

    /**
     * Регистрирует обработчик для интента подтверждения (YANDEX.CONFIRM).
     *
     * @param Closure|array|string $handler Обработчик
     * @return Event
     */
    public function onConfirm(Closure|array|string $handler): Event
    {
        return $this->onIntent('YANDEX.CONFIRM', $handler);
    }

    /**
     * Регистрирует обработчик для интента отказа (YANDEX.REJECT).
     *
     * @param Closure|array|string $handler Обработчик
     * @return Event
     */
    public function onReject(Closure|array|string $handler): Event
    {
        return $this->onIntent('YANDEX.REJECT', $handler);
    }

    /**
     * Регистрирует обработчик для интента помощи (YANDEX.HELP).
     *
     * @param Closure|array|string $handler Обработчик
     * @return Event
     */
    public function onHelp(Closure|array|string $handler): Event
    {
        return $this->onIntent('YANDEX.HELP', $handler);
    }

    /**
     * Регистрирует обработчик для интента повтора (YANDEX.REPEAT).
     *
     * @param Closure|array|string $handler Обработчик
     * @return Event
     */
    public function onRepeat(Closure|array|string $handler): Event
    {
        return $this->onIntent('YANDEX.REPEAT', $handler);
    }

    /**
     * Регистрирует обработчик для интента "что ты умеешь" (YANDEX.WHAT_CAN_YOU_DO).
     *
     * @param Closure|array|string $handler Обработчик
     * @return Event
     */
    public function onWhatCanYouDo(Closure|array|string $handler): Event
    {
        return $this->onIntent('YANDEX.WHAT_CAN_YOU_DO', $handler);
    }

    /**
     * Регистрирует обработчик для опасного контекста (dangerous_context).
     *
     * @param Closure|array|string $handler Обработчик
     * @return Event
     */
    public function onDangerous(Closure|array|string $handler): Event
    {
        return $this->on(['request.markup.dangerous_context' => true], $handler);
    }

    /**
     * Регистрирует обработчик подтверждения покупки.
     *
     * @param Closure|array|string $handler Обработчик
     * @return Event
     */
    public function onPurchaseConfirmation(Closure|array|string $handler): Event
    {
        return $this->on(['request.type' => 'Purchase.Confirmation'], $handler);
    }

    /**
     * Регистрирует обработчик события Show.Pull.
     *
     * @param Closure|array|string $handler Обработчик
     * @return Event
     */
    public function onShowPull(Closure|array|string $handler): Event
    {
        return $this->on(['request.type' => 'Show.Pull'], $handler);
    }

    /**
     * Регистрирует обработчик события начала воспроизведения AudioPlayer.
     *
     * @param Closure|array|string $handler Обработчик
     * @return Event
     */
    public function onAudioPlayerPlaybackStarted(Closure|array|string $handler): Event
    {
        return $this->on(['request.type' => 'AudioPlayer.PlaybackStarted'], $handler);
    }

    /**
     * Регистрирует обработчик события завершения воспроизведения AudioPlayer.
     *
     * @param Closure|array|string $handler Обработчик
     * @return Event
     */
    public function onAudioPlayerPlaybackFinished(Closure|array|string $handler): Event
    {
        return $this->on(['request.type' => 'AudioPlayer.PlaybackFinished'], $handler);
    }

    /**
     * Регистрирует обработчик события почти завершенного воспроизведения AudioPlayer.
     *
     * @param Closure|array|string $handler Обработчик
     * @return Event
     */
    public function onAudioPlayerPlaybackNearlyFinished(Closure|array|string $handler): Event
    {
        return $this->on(['request.type' => 'AudioPlayer.PlaybackNearlyFinished'], $handler);
    }

    /**
     * Регистрирует обработчик остановки воспроизведения AudioPlayer.
     *
     * @param Closure|array|string $handler Обработчик
     * @return Event
     */
    public function onAudioPlayerPlaybackStopped(Closure|array|string $handler): Event
    {
        return $this->on(['request.type' => 'AudioPlayer.PlaybackStopped'], $handler);
    }

    /**
     * Регистрирует обработчик ошибки воспроизведения AudioPlayer.
     *
     * @param Closure|array|string $handler Обработчик
     * @return Event
     */
    public function onAudioPlayerPlaybackFailed(Closure|array|string $handler): Event
    {
        return $this->on(['request.type' => 'AudioPlayer.PlaybackFailed'], $handler);
    }
}
