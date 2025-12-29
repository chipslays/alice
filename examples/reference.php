<?php

require __DIR__ . '/../vendor/autoload.php';

use Alice\Alice;
use Alice\Context;

$alice = new Alice;

$alice->fake(file_get_contents(__DIR__ . '/context/command.json'));

$alice->on(['request.command' => 'hello {who?}'], function (Context $context, Alice $alice, ?string $who = null) {
    dump('Привет! Чем могу помочь?', $who);
})->priority(10);

// $alice->group(function () use ($group) {
//     $group->on(['request.command' => '/пока/i'], function (Context $context, Alice $alice) {

//     });
// })->middleware('test');

$alice->dispatch();