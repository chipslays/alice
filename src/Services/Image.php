<?php

namespace Alice\Services;

use CURLFile;
use CurlHandle;

class Image
{
    protected string $host = 'https://dialogs.yandex.net';

    protected CurlHandle $httpClient;

    protected array $oncedData = [];

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
     * Получить список загруженных изображений.
     *
     * @return array
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
     * @param string $id
     * @return array
     */
    public function delete(string $id): array
    {
        $endpoint = $this->host . '/api/v1/skills/' . $this->skillId . '/images/' . $id;

        curl_setopt($this->httpClient, CURLOPT_URL, $endpoint);
        curl_setopt($this->httpClient, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($this->httpClient, CURLOPT_HTTPHEADER, [
            'Authorization: OAuth ' . $this->token,
        ]);

        $response = curl_exec($this->httpClient);

        return $this->handle($response);
    }

    /**
     * Загрузить локльное изображение или по ссылке.
     *
     * Если изображение уже было загружено и закешировано,
     * то вернет результат из кеша.
     *
     * Чтобы исключить кеш, укажите параметр `cache` как `false`.
     *
     * @param string $image
     * @param boolean $cache
     * @return string|null
     */
    public function upload(string $image, bool $cache = true): ?string
    {
        // Пробуем достать картинку из кеша
        if ($cache && $imageId = $this->retrieve($image)) {
            return $imageId;
        }

        if (!file_exists($image)) {
            $response = $this->uploadByUrl($image);
        } else {
            $response = $this->uploadByFile($image);
        }

        // Если ответ не содержит картинку
        if (!isset($response['image']['id'])) {
            return null;
        }

        $imageId = $response['image']['id'];

        // Кешируем картинку
        if ($cache) {
            file_put_contents($this->path . '/' . md5($image), $imageId, LOCK_EX);
        }

        return $imageId;
    }

    /**
     * Отправить картинку только один раз.
     *
     * После отправки изображение будет сразу удалено из Диалогов и кеша.
     *
     * Если изображение уже было загружено и закешировано,
     * то возьем изображение из кеша.
     *
     * Чтобы исключить кеш, укажите параметр `cache` как `false`.
     *
     * @param string $image
     * @param boolean $cache
     * @return string|null
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
     * @return array
     */
    public function uploadByUrl(string $url): array
    {
        $endpoint = $this->host . '/api/v1/skills/' . $this->skillId . '/images';

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
     * Загрузить локальное изображение.
     *
     * @param string $file
     * @return array
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
        return @file_get_contents($this->path . '/' . md5($image));
    }

    /**
     * Удалить изображение из кеша.
     *
     * @param string $image Ссылка или путь до локального файла.
     * @return self
     */
    public function forget(string $image): static
    {
        unlink($this->path . '/' . md5($image));

        return $this;
    }

    /**
     * @param string $response
     * @param string|null $image
     * @return array
     */
    protected function handle(string $response): array
    {
        return json_decode($response, true);
    }

    public  function __destruct()
    {
        foreach ($this->oncedData as $id => $item) {
            $this->delete($id);

            // Если картинка закеширована, удаляем кеш тоже
            if ($item['cache']) {
                $this->forget($item['image']);
            }
        }
    }
}