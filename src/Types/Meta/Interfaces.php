<?php

namespace Alice\Types\Meta;

class Interfaces
{
    public function __construct(protected array $interfaces = [])
    {
        //
    }

    public function hasScreen(): bool
    {
        return array_key_exists('screen', $this->interfaces);
    }

    public function hasPayments(): bool
    {
        return array_key_exists('payments', $this->interfaces);
    }

    public function hasAudioPlayer(): bool
    {
        return array_key_exists('audio_player', $this->interfaces);
    }

    public function hasAccountLinking(): bool
    {
        return array_key_exists('account_linking', $this->interfaces);
    }

    public function all(): array
    {
        return $this->toArray();
    }

    public function toArray(): array
    {
        return $this->interfaces;
    }
}