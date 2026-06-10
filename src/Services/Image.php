<?php

namespace Alice\Services;

use CURLFile;
use CurlHandle;

/**
 * Сервис для загрузки и управления изображениями в Yandex Dialogs.
 *
 * Предоставляет методы для статуса, загрузки (файла или по URL), удаления и кеширования.
 */
class Image
{
    protected string $host = 'https://dialogs.yandex.net';

    protected CurlHandle $httpClient;

    /** @var array<int|string,mixed> */
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

        $this->path = $root . '/' . $this->skillId . '/cache/images';

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
     * @return array<string,mixed>
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
     * Получить список загруженных изображений.
     *
     * @return array<string,mixed>
     */
    public function all(): array
    {
        $endpoint = $this->host . '/api/v1/skills/' . $this->skillId . '/images';

        curl_setopt($this->httpClient, CURLOPT_URL, $endpoint);
        curl_setopt($this->httpClient, CURLOPT_HTTPHEADER, [
            'Authorization: OAuth ' . $this->token,
        ]);

        $response = curl_exec($this->httpClient);

        return $this->handle($response);
    }

    /**
     * Удалить изображение из Диалогов.
     *
     * @param string|int $id
     * @return array<string,mixed>
     */
    public function delete(string|int $id): array
    {
        $endpoint = $this->host . '/api/v1/skills/' . $this->skillId . '/images/' . (string) $id;

        curl_setopt($this->httpClient, CURLOPT_URL, $endpoint);
        curl_setopt($this->httpClient, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($this->httpClient, CURLOPT_HTTPHEADER, [
            'Authorization: OAuth ' . $this->token,
        ]);

        $response = curl_exec($this->httpClient);

        return $this->handle($response);
    }

    /**
     * Загрузить локальное изображение или по URL.
     *
     * Если изображение уже было загружено и закешировано — вернётся значение из кеша.
     * Чтобы исключить кеширование, установите `$cache` в false.
     *
     * @param string $image Путь к файлу или URL
     * @param bool $cache Использовать кеш
     * @return string|null Идентификатор изображения или null при ошибке
     */
    public function upload(string $image, bool $cache = true): ?string
    {
        // Пробуем достать картинку из кеша
        if ($cache && $imageId = $this->retrieve($image)) {
            return $imageId;
        }

        if (!file_exists($image)) {
            /** @var array<string,mixed> $response */
            $response = (array) $this->uploadByUrl($image);
        } else {
            /** @var array<string,mixed> $response */
            $response = (array) $this->uploadByFile($image);
        }

        // Если ответ не содержит картинку или id — возвращаем null
        if (!isset($response['image']) || !is_array($response['image']) || !isset($response['image']['id'])) {
            return null;
        }

        $idVal = $response['image']['id'];
        if (!is_scalar($idVal)) {
            return null;
        }

        $imageId = (string) $idVal;

        // Кешируем картинку
        if ($cache) {
            file_put_contents($this->path . '/' . md5($image), $imageId, LOCK_EX);
        }

        return $imageId;
    }

    /**
     * Загрузить изображение единоразово (после использования удалится из Dialogs).
     *
     * Если изображение уже было закешировано — вернётся значение из кеша.
     * Чтобы исключить кеширование, установите `$cache` в false.
     *
     * @param string $image Путь к файлу или URL
     * @param bool $cache Использовать кеш
     * @return string|null Идентификатор изображения или null при ошибке
     */
    public function once(string $image, bool $cache = true): ?string
    {
        $id = $this->upload($image, $cache);

        if (!$id) {
            return null;
        }

        // Удалится в __destruct()
        $this->oncedData[$id] = compact('image', 'cache');

        return $id;
    }

    /**
     * Загрузить изображение по ссылке.
     *
     * @param string $url
     * @return array<string,mixed>
     */
    public function uploadByUrl(string $url): array
    {
        $endpoint = $this->host . '/api/v1/skills/' . $this->skillId . '/images';

        $payload = json_encode(compact('url')) ?: '{}';

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
     * Загрузить локальное изображение.
     *
     * @param string $file
     * @return array<string,mixed>
     */
    public function uploadByFile(string $file): array
    {
        $endpoint = $this->host . '/api/v1/skills/' . $this->skillId . '/images';

        $payload = ['file' => new CURLFile($file)];

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
     * Получить идентификатор изображения из кеша.
     *
     * @param string $image Ссылка или путь до локального файла.
     * @return string|null
     */
    public function retrieve(string $image): ?string
    {
        $content = @file_get_contents($this->path . '/' . md5($image));
        return $content === false ? null : $content;
    }

    /**
     * Удалить изображение из кеша.
     *
     * @param string $image Ссылка или путь до локального файла.
     * @return static
     */
    public function forget(string $image): static
    {
        unlink($this->path . '/' . md5($image));

        return $this;
    }

    /**
     * Декодирует HTTP-ответ из сервиса.
     *
     * @param bool|string $response Ответ от cURL
     * @return array<string,mixed>
     */
    protected function handle(bool|string $response): array
    {
        $decoded = json_decode((string) $response, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Деструктор: удаляет одноразовые ресурсы (once-uploaded) и очищает кеш, если требуется.
     *
     * @return void
     */
    public  function __destruct()
    {
        foreach ($this->oncedData as $id => $item) {
            $this->delete($id);

            // Если картинка закеширована, удаляем кеш тоже
            if (is_array($item) && !empty($item['cache']) && isset($item['image'])) {
                $this->forget((string) $item['image']);
            }
        }
    }
}