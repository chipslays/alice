<?php

declare(strict_types=1);

namespace Alice\Support;

use Alice\Support\Assets;

class Render
{
    /**
     * Точка входа.
     */
    public static function process(array $value): array
    {
        $text = self::compile($value['text'], 'text');
        $tts = self::compile($value['tts'], 'tts');

        return [
            'text' => self::finalize($text, 'text'),
            'tts'  => self::finalize($tts, 'tts'),
        ];
    }

    /**
     * Обработка с белым списком директив.
     * Полезно для кнопок, где не нужны audio, pause и tts.
     *
     * @param string $text Исходный текст
     * @param array $allowed Список разрешенных директив ['plural', 'rand', 'quotes']
     */
    public static function processOnly(string $text, array $allowed = []): string
    {
        // 1. Компилируем только разрешенные директивы
        // Передаем null в mode, так как для кнопок нет разделения на text/tts
        $compiled = self::compile($text, null, $allowed);

        // 2. Финализируем (кавычки, трим)
        return self::finalize($compiled, 'text');
    }

    /**
     * Обновленный метод compile с поддержкой фильтрации.
     */
    private static function compile(string $value, ?string $mode = null, array $allowed = []): string
    {
        $pattern = '/\@([a-zA-Z0-9_]+)\s*(\((?:[^()\'"\\\\]|\\\\.|(?:\'(?:\\\\.|[^\'\\\\])*\')|(?:"(?:\\\\.|[^"\\\\])*")|(?2))*\))/s';

        return preg_replace_callback($pattern, function ($matches) use ($mode, $allowed) {
            $name = $matches[1];
            $rawArgs = $matches[2];

            // Если список $allowed не пуст И директивы нет в списке — пропускаем (возвращаем как текст)
            if (!empty($allowed) && !in_array($name, $allowed, true)) {
                return $matches[0];
            }

            $content = substr($rawArgs, 1, -1);

            // Рекурсия: передаем те же ограничения внутрь
            $content = self::compile($content, $mode, $allowed);

            $method = 'directive' . ucfirst($name);
            if (method_exists(self::class, $method)) {
                // Если mode === null (как в кнопках), некоторые директивы могут вести себя иначе,
                // но обычно для кнопок мы хотим поведение как для 'text'.
                return self::$method($content, $mode ?? 'text');
            }

            return $matches[0];
        }, $value);
    }

    // --- Обработчики директив ---

    /**
     * @pause(500)
     */
    private static function directivePause(string $args, string $mode): string
    {
        if ($mode === 'text') return '';
        return 'sil <[' . self::variant(self::unwrap($args)) . ']>';
    }

    /**
     * @text(Текст)
     */
    private static function directiveText(string $args, string $mode): string
    {
        return $mode === 'text' ? self::unwrap($args) : '';
    }

    /**
     * @tts(Голос)
     */
    private static function directiveTts(string $args, string $mode): string
    {
        return $mode === 'tts' ? self::unwrap($args) : '';
    }

    /**
     * @br() или @br(2)
     */
    private static function directiveBr(string $args, string $mode): string
    {
        if ($mode === 'tts') return '';
        $count = (int) ($args ?: 1);
        return str_repeat("\n", $count);
    }

    /**
     * @space()
     */
    private static function directiveSpace(string $args, string $mode): string
    {
        return $mode === 'tts' ? ' ' : '';
    }

    /**
     * @rand(A|B)
     */
    private static function directiveRand(string $args, string $mode): string
    {
        return self::variant(self::unwrap($args));
    }

    /**
     * @plural(5, [яблоко, яблока, яблок])
     */
    private static function directivePlural(string $args, string $mode): string
    {
        // 1. Разбиваем аргументы (учитываем [], "", '')
        $params = self::parseArgs($args);

        $count = (int) ($params[0] ?? 0);
        $formsRaw = $params[1] ?? '';

        // 2. Убираем квадратные скобки массива
        $formsRaw = trim($formsRaw, '[]');

        // 3. Разбиваем содержимое массива
        $forms = self::parseArgs($formsRaw);

        // 4. Чистим кавычки у слов
        $forms = array_map(fn($f) => self::unwrap($f), $forms);

        // Вызываем глобальный хелпер (если есть) или фоллбэк
        if (function_exists('plural')) {
            return plural($count, $forms);
        }
        return $count . ' ' . ($forms[2] ?? end($forms) ?? '');
    }

    /**
     * @effect(megaphone, Текст)
     */
    private static function directiveEffect(string $args, string $mode): string
    {
        $params = self::parseArgs($args);
        $effect = self::unwrap($params[0] ?? '');
        $content = self::unwrap($params[1] ?? '');

        if ($mode === 'text') return $content;

        return '<speaker effect="' . self::variant($effect) . '">' . $content . '<speaker effect="-">';
    }

    /**
     * @audio(cat.mp3)
     */
    private static function directiveAudio(string $args, string $mode): string
    {
        if ($mode === 'text') return '';

        $variant = self::variant(self::unwrap($args));

        if (class_exists(Assets::class)) {
            $variant = Assets::get($variant) ?? $variant;
        }

        if (!str_ends_with($variant, '.opus')) {
            $variant .= '.opus';
        }

        return '<speaker audio="' . $variant . '">';
    }

    /**
     * @mixed(Текст, Голос)
     */
    private static function directiveMixed(string $args, string $mode): string
    {
        $params = self::parseArgs($args);
        return $mode === 'text'
            ? self::unwrap($params[0] ?? '')
            : self::unwrap($params[1] ?? '');
    }

    // --- Утилиты ---

    /**
     * Финальная постобработка строки.
     */
    public static function finalize(string $content, string $mode): string
    {
        // 1. Замена спец-кавычек <<< >>> на « »
        $content = str_replace(['<<<', '>>>'], ['«', '»'], $content);

        // 2. Убираем акценты (плюс перед буквой) только для экрана
        if ($mode === 'text') {
            $content = preg_replace('/\+(?=[a-zA-Zа-яА-Яё])/iu', '', $content);
        }

        // 3. Схлопываем множественные пробелы в один
        // 4. Триммим каждую строку
        // 5. Собираем обратно
        return trim(implode("\n", array_map('trim', explode("\n", preg_replace('/ {2,}/', ' ', $content)))));
    }

    /**
     * Умное разбиение аргументов по запятой.
     * Учитывает кавычки "" '' и скобки [].
     */
    private static function parseArgs(string $argsRaw): array
    {
        $result = [];
        $buffer = '';
        $inQuote = false;
        $quoteChar = '';
        $bracketBalance = 0;
        $len = strlen($argsRaw);

        for ($i = 0; $i < $len; $i++) {
            $char = $argsRaw[$i];

            // Кавычки
            if (($char === '"' || $char === "'") && ($i === 0 || $argsRaw[$i-1] !== '\\')) {
                if (!$inQuote) {
                    $inQuote = true;
                    $quoteChar = $char;
                } elseif ($char === $quoteChar) {
                    $inQuote = false;
                }
            }

            // Скобки []
            if (!$inQuote) {
                if ($char === '[') $bracketBalance++;
                if ($char === ']') $bracketBalance--;
            }

            // Запятая-разделитель
            if ($char === ',' && !$inQuote && $bracketBalance === 0) {
                $result[] = trim($buffer);
                $buffer = '';
                continue;
            }

            $buffer .= $char;
        }

        $result[] = trim($buffer);

        // Удаляем пустые элементы
        return array_values(array_filter($result, fn($r) => $r !== ''));
    }

    public static function variant(string|array $variants): string
    {
        if (is_string($variants)) {
            if (!str_contains($variants, '|')) {
                return $variants;
            }
            $list = array_map('trim', explode('|', $variants));
        } else {
            $list = $variants;
        }

        return $list[array_rand($list)];
    }

    private static function unwrap(string $str): string
    {
        $str = trim($str);
        if (strlen($str) < 2) return $str;

        $first = $str[0];
        $last = substr($str, -1);

        if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
            return substr($str, 1, -1);
        }
        return $str;
    }
}
