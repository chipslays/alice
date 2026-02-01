<?php

namespace Alice\Types\Meta;

/**
 * Обёртка доступных интерфейсов платформы (screen, payments, audio_player, account_linking).
 */
class Interfaces
{
    /**
     * @param array<string,mixed> $interfaces
     */
    public function __construct(protected array $interfaces = [])
    {
        //
    }

    /**
     * Вернуть все интерфейсы.
     * @return array<string,mixed>
     */
    public function all(): array
    {
        return $this->toArray();
    }

    /**
     * Преобразовать в массив.
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return $this->interfaces;
    }

    /**
     * Есть ли поддержка экрана
     * @return bool
     */
    public function hasScreen(): bool
    {
        return array_key_exists('screen', $this->interfaces);
    }

    /**
     * Есть ли поддержка платежей
     * @return bool
     */
    public function hasPayments(): bool
    {
        return array_key_exists('payments', $this->interfaces);
    }

    /**
     * Есть ли поддержка AudioPlayer
     * @return bool
     */
    public function hasAudioPlayer(): bool
    {
        return array_key_exists('audio_player', $this->interfaces);
    }

    /**
     * Есть ли поддержка аккаунт-линкинга
     * @return bool
     */
    public function hasAccountLinking(): bool
    {
        return array_key_exists('account_linking', $this->interfaces);
    }
}