<?php
// Устанавливает глобальную конфигурацию git hooks path на .githooks и делает скрипты исполняемыми (если возможно)
$projectRoot = dirname(__DIR__);
$hooksPath = $projectRoot . DIRECTORY_SEPARATOR . '.githooks';

// Попытка установить config core.hooksPath
exec('git config core.hooksPath ' . escapeshellarg('.githooks'), $output, $status);
if ($status === 0) {
    echo "Configured git hooks path to .githooks\n";
} else {
    echo "Warning: could not set git core.hooksPath automatically. Run manually: git config core.hooksPath .githooks\n";
}

// Попробуем установить права на исполнение (линукс/мак)
$pre = $hooksPath . DIRECTORY_SEPARATOR . 'pre-commit';
if (file_exists($pre)) {
    @chmod($pre, 0755);
}

return 0;
