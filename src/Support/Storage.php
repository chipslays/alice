<?php

declare(strict_types=1);

namespace Alice\Support;

use Alice\Settings;
use Alice\Support\Container;
use Closure;
use RuntimeException;
use Throwable;

class Storage
{
    protected string $directory;

    /**
     * Конструктор. Инициализирует директорию хранения; если путь не указан —
     * используется временная директория по умолчанию для текущего skill.
     *
     * @param string|null $path Путь к директории хранения
     */
    public function __construct(?string $path = null)
    {
        /** @var Settings $settings */
        $settings = Container::getInstance()->get(Settings::class);

        if ($path === null) {
            $skillId = $this->getSkillId() ?? '_unskilled';
            $path =
                $settings->get('storage.path', sys_get_temp_dir()) .
                DIRECTORY_SEPARATOR .
                'alice' .
                DIRECTORY_SEPARATOR .
                'storage' .
                DIRECTORY_SEPARATOR .
                $skillId;
        }

        $this->directory = rtrim($path, '/\\');

        $this->ensureDirectoryExists();
    }

    /**
     * Сохранить значение.
     */
    public function set(string $key, mixed $value, int $ttl = 0): static
    {
        $filename = $this->getFilename($key);

        $payload = [
            'expires_at' => $ttl > 0 ? time() + $ttl : null,
            'data' => $value,
        ];

        $temp = $filename . '.tmp';

        if (file_put_contents($temp, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) === false) {
            throw new RuntimeException("Не удалось записать файл: $temp");
        }

        rename($temp, $filename);

        return $this;
    }

    /**
     * Получить значение.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $payload = $this->readPayload($key);

        if ($payload === null) {
            return $this->resolveDefault($default);
        }

        return $payload['data'];
    }

    /**
     * Проверить существование ключа.
     * Теперь это честная проверка: файл есть + JSON валиден + TTL не истек.
     */
    public function has(string $key): bool
    {
        return $this->readPayload($key) !== null;
    }

    /**
     * Удалить ключ.
     */
    public function remove(string $key): bool
    {
        $filename = $this->getFilename($key);

        if (file_exists($filename)) {
            return unlink($filename);
        }

        return false;
    }

    /**
     * Очистить всё хранилище.
     */
    public function clear(): void
    {
        $files = glob($this->directory . DIRECTORY_SEPARATOR . '*.json');
        if (!is_array($files)) {
            return;
        }

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Читает файл, проверяет структуру и TTL.
     * Если файл протух или битый — удаляет его и возвращает null.
     *
     * @param string $key
     * @return array<string,mixed>|null
     */
    protected function readPayload(string $key): ?array
    {
        $filename = $this->getFilename($key);

        if (!file_exists($filename)) {
            return null;
        }
        $content = file_get_contents($filename);
        if ($content === false) {
            $this->remove($key);
            return null;
        }

        $payload = json_decode($content, true);

        // 1. Проверка структуры
        if (!is_array($payload) || !array_key_exists('data', $payload)) {
            $this->remove($key); // Удаляем битый файл
            return null;
        }

        // 2. Проверка TTL
        if (isset($payload['expires_at']) && is_int($payload['expires_at'])) {
            if (time() > $payload['expires_at']) {
                $this->remove($key); // Удаляем протухший файл
                return null;
            }
        }

        return $payload;
    }

    protected function getFilename(string $key): string
    {
        return $this->directory . DIRECTORY_SEPARATOR . md5($key) . '.json';
    }

    protected function ensureDirectoryExists(): void
    {
        if (!is_dir($this->directory)) {
            if (!mkdir($this->directory, 0755, true) && !is_dir($this->directory)) {
                throw new RuntimeException("Не удалось создать директорию хранилища: {$this->directory}");
            }
        }
    }

    protected function resolveDefault(mixed $default): mixed
    {
        if ($default instanceof Closure) {
            return Container::getInstance()->call($default);
        }

        return $default;
    }

    protected function getSkillId(): ?string
    {
        try {
            /** @var Settings $settings */
            $settings = Container::getInstance()->get(Settings::class);
            $skillId = $settings->get('skill_id');
            return is_scalar($skillId) ? (string) $skillId : null;
        } catch (Throwable) {
            return null;
        }
    }
}
