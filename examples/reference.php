<?php

require __DIR__ . '/../vendor/autoload.php';

use Alice\Alice;
use Alice\Context;
use Alice\Scenes\Scene;
use Alice\Scenes\Stage;
use Alice\State\Session;
use Alice\Types\Button;

$alice = new Alice;

$alice->fake(file_get_contents(__DIR__ . '/context/command.json'));

defer(function (Context $context, Alice $alice) {
    var_export('asd');
});

$alice->onScene('feedback', function (Scene $scene) {
    $scene->onEnter(function (Context $context, Alice $alice) {
        $alice->reply('Какая у вас проблема?');
    });

    // Будет ловить любой текст внутри этой сцены
    $scene->onAny(function (Context $context, Alice $alice, Session $session) {
        $alice->reply('Отправить?', buttons: [new Button('Да', 'yes')]);
    });

    $scene->onAction('yes', function (Context $context, Alice $alice, Stage $stage) {
        $alice->reply('Отправлено!');
        $context->leave();
    });
});

// Глобальная команда для входа
$alice->onCommand('поддержка', function (Context $context) {
    $context->enter('feedback');
});

$alice->dispatch();