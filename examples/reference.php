<?php

require __DIR__ . '/../vendor/autoload.php';

use Alice\Alice;

$alice = new Alice;

$alice->fake(file_get_contents(__DIR__ . '/context/repeat.json'));

$alice->onRepeat(function (Alice $alice) {
    $alice->reply('repeat');
});

$alice->dispatch();