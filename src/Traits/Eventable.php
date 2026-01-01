<?php

namespace Alice\Traits;

use Alice\Context;
use Alice\Events\Dispatcher;
use Alice\Events\Event;
use Alice\Events\Group;
use Closure;

trait Eventable
{
    protected ?Dispatcher $eventDispatcher = null;

    public function on(Closure|array|string $rules, Closure|callable|array|string $handler): Event
    {
        return $this->getEventDispatcher()->add($rules, $handler);
    }

    public function group(Closure $callback): Group
    {
        return $this->getEventDispatcher()->group(function() use ($callback) {
            $callback($this);
        });
    }

    public function middleware(string|array|Closure $middleware): self
    {
        $this->getEventDispatcher()->pipe($middleware);

        return $this;
    }

    abstract protected function getEventDispatcher(): Dispatcher;

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

    public function onCommand(array|string $command, Closure|array|string $handler): Event
    {
        return $this->on(['request.command' => $command], $handler);
    }

    public function onAction(array|string $action, Closure|array|string $handler): Event
    {
        return $this->on(['request.payload.__action__' => $action], $handler);
    }

    public function onAny(Closure|array|string $handler): Event
    {
        return $this->on('request.type', $handler);
    }

    public function onIntent(array|string $id, Closure|array|string $handler): Event
    {
        $rule = function (Context $context) use ($id): bool {
            return (bool) array_intersect((array) $id, array_keys(
                $context->request->get('request.nlu.intents')->toArray()
            ));
        };

        return $this->on($rule, $handler);
    }

    public function onConfirm(Closure|array|string $handler): Event
    {
        return $this->onIntent('YANDEX.CONFIRM', $handler);
    }

    public function onReject(Closure|array|string $handler): Event
    {
        return $this->onIntent('YANDEX.REJECT', $handler);
    }

    public function onHelp(Closure|array|string $handler): Event
    {
        return $this->onIntent('YANDEX.HELP', $handler);
    }

    public function onRepeat(Closure|array|string $handler): Event
    {
        return $this->onIntent('YANDEX.REPEAT', $handler);
    }

    public function onWhatCanYouDo(Closure|array|string $handler): Event
    {
        return $this->onIntent('YANDEX.WHAT_CAN_YOU_DO', $handler);
    }

    public function onDangerous(Closure|array|string $handler): Event
    {
        return $this->on(['request.markup.dangerous_context' => true], $handler);
    }

    public function onPurchaseConfirmation(Closure|array|string $handler): Event
    {
        return $this->on(['request.type' => 'Purchase.Confirmation'], $handler);
    }

    public function onShowPull(Closure|array|string $handler): Event
    {
        return $this->on(['request.type' => 'Show.Pull'], $handler);
    }

    public function onAudioPlayerPlaybackStarted(Closure|array|string $handler): Event
    {
        return $this->on(['request.type' => 'AudioPlayer.PlaybackStarted'], $handler);
    }

    public function onAudioPlayerPlaybackFinished(Closure|array|string $handler): Event
    {
        return $this->on(['request.type' => 'AudioPlayer.PlaybackFinished'], $handler);
    }

    public function onAudioPlayerPlaybackNearlyFinished(Closure|array|string $handler): Event
    {
        return $this->on(['request.type' => 'AudioPlayer.PlaybackNearlyFinished'], $handler);
    }

    public function onAudioPlayerPlaybackStopped(Closure|array|string $handler): Event
    {
        return $this->on(['request.type' => 'AudioPlayer.PlaybackStopped'], $handler);
    }

    public function onAudioPlayerPlaybackFailed(Closure|array|string $handler): Event
    {
        return $this->on(['request.type' => 'AudioPlayer.PlaybackFailed'], $handler);
    }
}
