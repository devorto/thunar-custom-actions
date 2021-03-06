<?php

namespace App;

use App\Commands\Archive\Create;
use App\Commands\Archive\Extract;
use App\Commands\Cue\Split;
use App\Commands\Install;
use App\Commands\Terminal\OpenHere;
use App\Commands\Update;
use Devorto\DependencyInjection\DependencyInjection;
use Symfony\Component\Console\Application;

require_once __DIR__ . '/../vendor/autoload.php';

// Note: Make sure version always matches the latest tag for self-update command.
$app = new Application('Thunar Custom Actions', '1.1.0-alpha.1');

$commands = [
    Update::class,
    Install::class,
    Split::class,
    Create::class,
    Extract::class,
    OpenHere::class
];

$app->addCommands(array_map(
    function (string $class) {
        return DependencyInjection::instantiate($class);
    },
    $commands
));

$app->run();
