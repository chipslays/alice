<?php

namespace Alice\Services;

use CURLFile;
use CurlHandle;

/**
 * Сервис для загрузки и управления звуками в Yandex Dialogs.
 *
 * Предоставляет методы для статуса, загрузки (файла или по URL), удаления и кеширования.
 */
class Sound
{
    protected string $host = 'https://dialogs.yandex.net';

    protected CurlHandle $httpClient;

    protected array $oncedData = [];

    /**
     * @param string $token Токен OAuth
     * @param string $skillId Идентификатор навыка
     * @param string|null $path Путь для локального кеша
     */
    public function __construct(protected string $token, protected string $skillId, protected ?string $path = null)
    {
        $path ??= sys_get_temp_dir() . '/alice';

        $root = rtrim($path, '\/');

        $this->path = $root . '/' . $this->skillId . '/sounds';

        if (!file_exists($this->path)) {
            mkdir($this->path, recursive: true);
        }

        $this->httpClient = curl_init();
        curl_setopt($this->httpClient, CURLOPT_RETURNTRANSFER, true);
    }

    /**
     * Проверить занятое место.
     *
     * Для каждого аккаунта Яндекса на Диалоги
     * можно загрузить не больше 100 МБ картинок.
     *
     * @return array
     */
    public function status(): array
    {
        $endpoint = $this->host . '/api/v1/status';

        curl_setopt($this->httpClient, CURLOPT_URL, $endpoint);
        curl_setopt($this->httpClient, CURLOPT_HTTPHEADER, [
            'Authorization: OAuth ' . $this->token,
        ]);

        $response = curl_exec($this->httpClient);

        return $this->handle($response);
    }

    /**
     * Получить список загруженных звуков.
     *
     * @return array
     */
    public function all(): array
    {
        $endpoint = $this->host . '/api/v1/skills/' . $this->skillId . '/sounds';

        curl_setopt($this->httpClient, CURLOPT_URL, $endpoint);
        curl_setopt($this->httpClient, CURLOPT_HTTPHEADER, [
            'Authorization: OAuth ' . $this->token,
        ]);

        $response = curl_exec($this->httpClient);

        return $this->handle($response);
    }

    /**
     * Удалить звук из Диалогов.
     *
     * @param string $id
     * @return array
     */
    public function delete(string $id): array
    {
        $endpoint = $this->host . '/api/v1/skills/' . $this->skillId . '/sounds/' . $id;

        curl_setopt($this->httpClient, CURLOPT_URL, $endpoint);
        curl_setopt($this->httpClient, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($this->httpClient, CURLOPT_HTTPHEADER, [
            'Authorization: OAuth ' . $this->token,
        ]);

        $response = curl_exec($this->httpClient);

        return $this->handle($response);
    }

    /**
     * Загрузить локальный звук или по URL.
     *
     * Если звук уже был загружен и закеширован — вернётся значение из кеша.
     * Чтобы исключить кеширование, установите `$cache` в false.
     *
     * @param string $sound Путь к файлу или URL
     * @param bool $cache Использовать кеш
     * @return string|null Идентификатор звука или null при ошибке
     */
    public function upload(string $sound, bool $cache = true): ?string
    {
        // Пробуем достать картинку из кеша
        if ($cache && $soundId = $this->retrieve($sound)) {
            return $soundId;
        }

        if (!file_exists($sound)) {
            $response = $this->uploadByUrl($sound);
        } else {
            $response = $this->uploadByFile($sound);
        }

        // Если ответ не содержит картинку
        if (!isset($response['sound']['id'])) {
            return null;
        }

        $soundId = $response['sound']['id'];

        // Кешируем картинку
        if ($cache) {
            file_put_contents($this->path . '/' . md5($sound), $soundId, LOCK_EX);
        }

        return $soundId;
    }

    /**
     * Получить метаданные о звуке.
     *
     * @param string $sound Идентификатор или имя ресурса
     * @return array
     */
    public function info(string $sound): array
    {
        $endpoint = $this->host . '/api/v1/skills/' . $this->skillId . '/sounds/' . $sound;

        curl_setopt($this->httpClient, CURLOPT_URL, $endpoint);
        curl_setopt($this->httpClient, CURLOPT_HTTPHEADER, [
            'Authorization: OAuth ' . $this->token,
        ]);

        $response = curl_exec($this->httpClient);

        return $this->handle($response);
    }

    /**
     * Загрузить звук по ссылке.
     *
     * @param string $url
     * @return array
     */
    public function uploadByUrl(string $url): array
    {
        $endpoint = $this->host . '/api/v1/skills/' . $this->skillId . '/sounds';

        $payload = json_encode(compact('url'));

        curl_setopt($this->httpClient, CURLOPT_URL, $endpoint);
        curl_setopt($this->httpClient, CURLOPT_POST, true);
        curl_setopt($this->httpClient, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($this->httpClient, CURLOPT_HTTPHEADER, [
            'Authorization: OAuth ' . $this->token,
            'Content-Type: application/json',
        ]);

        $response = curl_exec($this->httpClient);

        return $this->handle($response);
    }

    /**
     * Загрузить локальное звук.
     *
     * @param string $file
     * @return array
     */
    public function uploadByFile(string $file): array
    {
        $endpoint = $this->host . '/api/v1/skills/' . $this->skillId . '/sounds';

        $payload = [
            'file' => new CURLFile($file),
        ];

        curl_setopt($this->httpClient, CURLOPT_URL, $endpoint);
        curl_setopt($this->httpClient, CURLOPT_POST, true);
        curl_setopt($this->httpClient, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($this->httpClient, CURLOPT_HTTPHEADER, [
            'Authorization: OAuth ' . $this->token,
            'Content-Type: multipart/form-data',
        ]);

        $response = curl_exec($this->httpClient);

        return $this->handle($response);
    }

    /**
     * Получить идентификатор звука из кеша.
     *
     * @param string $sound Ссылка или путь до локального файла.
     * @return string|null
     */
    public function retrieve(string $sound): ?string
    {
        return @file_get_contents($this->path . '/' . md5($sound));
    }

    /**
     * Удалить звук из кеша.
     *
     * @param string $sound Ссылка или путь до локального файла.
     * @return self
     */
    public function forget(string $sound): static
    {
        unlink($this->path . '/' . md5($sound));

        return $this;
    }

    /**
     * @param string $response
     * @param string|null $sound
     * @return array
     */
    protected function handle(string $response): array
    {
        return json_decode($response, true);
    }

    /**
     * Деструктор: удаляет одноразовые ресурсы (once-uploaded) и очищает кеш, если требуется.
     *
     * @return void
     */
    public function __destruct()
    {
        foreach ($this->oncedData as $id => $item) {
            $this->delete($id);

            // Если звук закеширован, удаляем кеш тоже
            if ($item['cache']) {
                $this->forget($item['sound']);
            }
        }
    }
}