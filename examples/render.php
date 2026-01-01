<?php

use Alice\Support\Render;

require __DIR__ . '/../vendor/autoload.php';

function test(string $name, string $input) {
    echo "TEST: $name\n";
    echo "INPUT: $input\n";

    $result = Render::process(['text' => $input, 'tts' => $input]);

    echo "TEXT:  " . $result['text'] . "\n";
    echo "TTS:   " . $result['tts'] . "\n";
    echo str_repeat("-", 40) . "\n";
}

echo "=== ЗАПУСК ТЕСТОВ RENDER ===\n\n";

// Тест 1: Простой текст и пауза
test(
    "База: Пауза",
    "Привет, @pause(500) как дела?"
);

// Тест 2: Скрытый текст (разный для экрана и голоса)
test(
    "Разделение Text/TTS",
    "Посмотрите на @text(экран) @tts(эту картинку)."
);

// Тест 3: Кавычки внутри аргументов (Сложный кейс!)
test(
    "Кавычки в аргументах",
    "Скажи: @text(\"Привет, (мир)!\") @tts('Hello (world)')"
);

// Тест 4: Вложенность (Рандом внутри Текста)
test(
    "Вложенность",
    "Я чувствую @text(себя @rand(отлично|хорошо))."
);

// Тест 5: Plural с массивом
test(
    "Склонение (Plural)",
    "У меня @plural(5, [яблоко, яблока, яблок]) и @plural(1, [кот, кота, котов])."
);

// Тест 6: Эффект (Effect) с запятой внутри текста
test(
    "Эффекты с запятыми",
    "@effect(megaphone, \"Внимание, поезд отправляется!\")"
);

// Тест 7: Mixed (Смешанный режим)
test(
    "Mixed (экран/голос)",
    "@mixed(\"Текст для глаз\", \"Текст для ушей\")"
);

// Тест 8: Переносы строк
test(
    "Переносы строк",
    "Строка 1 @br(2) Строка 2"
);