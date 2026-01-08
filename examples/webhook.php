<?php

require __DIR__ . '/../vendor/autoload.php';

use Alice\Alice;
use Alice\State\Application;
use Alice\Types\Button;

$alice = new Alice;

// $alice->onAny(function (Alice $alice) {
//     $alice->reply('Привет!', buttons: [new Button('Да', 'yes')]);
// });

$alice->onAction('yes', function (Alice $alice) {
    $alice->reply('Okay, YES!');
});

$alice->onRepeat(function (Alice $alice) {
    $alice->reply('repeat');
});

$alice->onFallback(function (Alice $alice, Application $application) {
    $application->increment('counter');
    $alice->reply('fallback!' . $application->get('counter'));
});

$alice->dispatch();