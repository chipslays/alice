<?php

namespace Alice;

use Alice\State\Application;
use Alice\State\Session;
use Alice\State\User;
use Alice\Support\Buttons;

/**
 * Формирует ответ навыка в формате, ожидаемом внешней платформой.
 *
 * Отвечает за текст, tts, кнопки, карточки, directives и сериализацию в JSON.
 */
use Alice\Support\Container;
use Alice\Types\Button;
use Alice\Types\Card\AbstractCard;
use Alice\Types\Directives\AudioPlayer\AudioPlayer;
use JsonException;

class Response
{
    /**
     * @var array{
     *     response: array{text?: string|null, tts?: string|null, buttons?: array<int,mixed>, end_session: bool},
     *     version: string,
     *     session_state?: array<int|string,mixed>,
     *     application_state?: array<int|string,mixed>,
     *     user_state_update?: array<int|string,mixed>
     * }
     */
    protected array $response = [
        'response' => [
            'text' => null,
            'end_session' => false,
        ],
        'version' => '1.0',
    ];

    /**
     * Устанавливает текст ответа.
     *
     * @param string $text Текст ответа
     * @return static
     */
    public function text(string $text): static
    {
        $this->response['response']['text'] = $text;

        return $this;
    }

    /**
     * Устанавливает TTS-версию текста.
     *
     * @param string $tts Текст для синтеза речи
     * @return static
     */
    public function tts(string $tts): static
    {
        $this->response['response']['tts'] = $tts;

        return $this;
    }

    /**
     * Добавляет кнопки к ответу.
     *
     * @param array<int,mixed>|string $buttons Массив кнопок или ключ из настроек buttons
     * @return static
     */
    public function withButtons(array|string $buttons): static
    {
        $buttons = is_string($buttons) ? Buttons::get($buttons) : $buttons;

        /** @var array<int,mixed> $buttonsArr */
        $buttonsArr = (array) $buttons;

        $this->response['response']['buttons'] = $this->resolveButtons($buttonsArr);

        return $this;
    }

    /**
     * @param array<int|string,mixed> $buttons
     * @return array<int,mixed>
     */
    protected function resolveButtons(array $buttons): array
    {
        return array_reduce(
            array_filter($buttons),
            function (array $carry, $button) {
                return array_merge(
                    $carry,
                    match (true) {
                        $button instanceof Button => [$button->toArray()],
                        is_array($button) => $this->resolveButtons((array) $button),
                        is_string($button) => (function($b) {
                            $sub = Buttons::get($b);
                            /** @var array<int,mixed> $subList */
                            $subList = is_array($sub) ? $sub : (array) $sub;
                            return $this->resolveButtons($subList);
                        })($button),
                        default => [],
                    }
                );
            },
            []
        );
    }

    /**
     * Добавляет карточку к ответу.
     *
     * @param AbstractCard $card
     * @return static
     */
    public function withCard(AbstractCard $card): static
    {
        $this->with([
            'response' => [
                'card' => $card->toArray(),
            ],
        ]);

        return $this;
    }

    /**
     * Добавляет AudioPlayer директиву к ответу.
     *
     * @param AudioPlayer $player
     * @return static
     */
    public function withAudioPlayer(AudioPlayer $player): static
    {
        $this->with([
            'response' => [
                'should_listen' => $player->autoplay,
                'directives' => [
                    'audio_player' => $player->toArray(),
                ],
            ],
        ]);

        return $this;
    }

    /**
     * Мержит дополнительные данные в ответ.
     *
     * @param array<string,mixed> $data Дополнительные данные для объединения
     * @return static
     */
    public function with(array $data = []): static
    {
        /** @var array{response: array{text?: string|null, tts?: string|null, buttons?: array<int,mixed>, end_session: bool}, version: string, session_state?: array<int|string,mixed>, application_state?: array<int|string,mixed>, user_state_update?: array<int|string,mixed>} $merged */
        $merged = array_replace_recursive($this->response, $data);
        $this->response = $merged;

        return $this;
    }

    /**
     * Устанавливает флаг завершения сессии.
     *
     * @param bool $value Завершать ли сессию
     * @return static
     */
    public function finish(bool $value = true): static
    {
        $this->response['response']['end_session'] = $value;

        return $this;
    }

    /**
     * Быстрый метод для отправки pong-ответа (диагностика).
     *
     * @return static
     */
    public function pong(): static
    {
        return $this
            ->text('pong')
            ->tts('pong')
            ->finish();
    }

    /**
     * @throws JsonException
     */
    public function __toString(): string
    {
        $this->addSessionData();

        return json_encode(
            $this->response,
            JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );
    }

    protected function addSessionData(): void
    {
        $container = Container::getInstance();

        /** @var Session $session */
        $session = $container->get(Session::class);
        if ($session->count() > 0) {
            /** @var array<int|string,mixed> $s */
            $s = $session->toArray();
            /** @var array{response: array{text?: string|null, tts?: string|null, buttons?: array<int,mixed>, end_session: bool}, version: string, session_state?: array<int|string,mixed>, application_state?: array<int|string,mixed>, user_state_update?: array<int|string,mixed>} $resp */
            $resp = $this->response;
            $resp['session_state'] = $s;
            $this->response = $resp;
        }

        /** @var Application $application */
        $application = $container->get(Application::class);
        if ($application->count() > 0) {
            /** @var array<int|string,mixed> $a */
            $a = $application->toArray();
            /** @var array{response: array{text?: string|null, tts?: string|null, buttons?: array<int,mixed>, end_session: bool}, version: string, session_state?: array<int|string,mixed>, application_state?: array<int|string,mixed>, user_state_update?: array<int|string,mixed>} $resp */
            $resp = $this->response;
            $resp['application_state'] = $a;
            $this->response = $resp;
        }

        /** @var User $user */
        $user = $container->get(User::class);
        if ($user->count() > 0) {
            /** @var array<int|string,mixed> $u */
            $u = $user->toArray();
            /** @var array{response: array{text?: string|null, tts?: string|null, buttons?: array<int,mixed>, end_session: bool}, version: string, session_state?: array<int|string,mixed>, application_state?: array<int|string,mixed>, user_state_update?: array<int|string,mixed>} $resp */
            $resp = $this->response;
            $resp['user_state_update'] = $u;
            $this->response = $resp;
        }
    }
}