<?php

require __DIR__ . '/../vendor/autoload.php';

use Alice\Alice;
use Alice\Context;
use Alice\Contracts\Middleware;
use Alice\Settings;

// --- 1. Подготовка тестовых классов (чтобы не создавать кучу файлов) ---

// Сервис, который мы будем инжектить в Middleware и Контроллеры
class LoggerService
{
    public function log(string $msg): void
    {
        echo "[LOGGER] $msg" . PHP_EOL;
    }
}

// Middleware 1: Глобальный, проверяет токен (с инъекцией зависимостей!)
class AuthMiddleware implements Middleware
{
    public function __construct(
        private LoggerService $logger, // <-- Инъекция сервиса в middleware
        private Settings $settings       // <-- Инъекция настроек Alice
    ) {}

    public function handle(Context $context, Closure $next): mixed
    {
        $this->logger->log("AuthMiddleware: Checking access...");

        // Допустим, мы берем секрет из опций
        $secret = $this->settings->get('secret_key') ?? '12345';

        // В реальном Paylaod нет поля token, но представим, что есть.
        // Для теста просто пропускаем.
        if ($secret !== '12345') {
            echo "Access Denied!\n";
            return false;
        }

        return $next($context);
    }
}

// Middleware 2: Для группы "admin"
class AdminMiddleware implements Middleware
{
    public function handle(Context $context, Closure $next): mixed
    {
        echo "[AdminMiddleware] Checking admin rights...\n";
        // Изменяем Context по ходу дела (например)
        $context->set('is_admin', true);

        return $next($context);
    }
}

// --- 2. Инициализация приложения ---

// Настройки приложения
$settings = new Settings(['secret_key' => '12345']);
$alice = new Alice($settings);

// Эмулируем входящий JSON (как будто пришел webhook)
$fakeJson = json_encode([
    'request' => ['command' => '/admin_delete'],
    'meta' => ['user_id' => 777]
]);
$alice->fake($fakeJson);


// --- 3. Настройка DI (Опционально) ---

// Если хотим использовать LoggerService, надо чтобы контейнер знал о нем
// (или он создастся сам, если нет конструктора, тут autowiring сработает)
// Но для чистоты можно зарегистрировать singleton:
$alice->container->singleton(LoggerService::class);


// --- 4. Регистрация Middleware и Событий ---

// A. Глобальный middleware
$alice->middleware(AuthMiddleware::class);


// B. Обычное событие
$alice->on(['request.command' => '/прив/i'], function (Context $p, Alice $a) {
    echo "Bot: Привет! (Глобальный middleware отработал)\n";
});


// C. Группы с Middleware
// Мы хотим, чтобы все команды внутри этой группы проходили через AdminMiddleware
$alice->group(function (Alice $groupAlice) {

    // Событие 1 внутри группы
    $groupAlice->on(['request.command' => '/admin_{command}'], function (
        Context $context,
        LoggerService $logger,
        string $command
    ) {
        dump($command);
        $cmd = $context->get('request.command');
        $isAdmin = $context->get('is_admin') ? 'YES' : 'NO';

        $logger->log("Executing admin command: $cmd (Is Admin: $isAdmin)");
        echo "Bot: Command executed.\n";
    });

    // Событие 2 внутри группы
    $groupAlice->on(['request.command' => '/ban'], function () {
        echo "Bot: Ban hammer!\n";
    });

})->middleware(AdminMiddleware::class); // <-- Применяем middleware ко всей группе


// --- 5. Запуск ---

echo "--- START DISPATCH ---\n";
$alice->dispatch();
echo "--- END DISPATCH ---\n";
