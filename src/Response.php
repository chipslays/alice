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
     * @param array|string $buttons Массив кнопок или ключ из настроек buttons
     * @return static
     */
    public function withButtons(array|string $buttons): static
    {
        $buttons = is_string($buttons) ? Buttons::get($buttons) : $buttons;

        $this->response['response']['buttons'] = $this->resolveButtons((array) $buttons);

        return $this;
    }

    protected function resolveButtons(array $buttons): array
    {
        return array_reduce(
            array_filter($buttons),
            function (array $carry, $button) {
                return array_merge(
                    $carry,
                    match (true) {
                        $button instanceof Button => [$button->toArray()],
                        is_array($button) => $this->resolveButtons($button),
                        is_string($button) => $this->resolveButtons(Buttons::get($button)),
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
     * @param array $data Дополнительные данные для объединения
     * @return static
     */
    public function with(array $data = []): static
    {
        array_replace_recursive($this->response, $data);

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

        $session = $container->get(Session::class);
        if ($session->count() > 0) {
            $this->response['session_state'] = $session->toArray();
        }

        $application = $container->get(Application::class);
        if ($application->count() > 0) {
            $this->response['application_state'] = $application->toArray();
        }

        $user = $container->get(User::class);
        if ($user->count() > 0) {
            $this->response['user_state_update'] = $user->toArray();
        }
    }
}