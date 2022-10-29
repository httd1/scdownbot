<?php

include __DIR__.'/vendor/autoload.php';
include __DIR__.'/class/SoundCloudPHP.php';
include __DIR__.'/class/SoundCloudBot.php';

// models
include __DIR__.'/class/Entity/Usuarios.php';
include __DIR__.'/class/Entity/Cache.php';

\TelegramPhp\Config\Token::setToken ('');

use TelegramPhp\TelegramPhp;
use TelegramPhp\Methods;
use TelegramPhp\Buttons;

$tlg = new TelegramPhp;

$tlg->command ('/start', 'SoundCloudBot:start');
$tlg->command ('/help', 'SoundCloudBot:help');

$tlg->commandMatch ('/https?:\/\/(?:(?:\w+)\.)?soundcloud(?:\.\w+)+\/[^\s]{3,}/', 'SoundCloudBot:downloadMusic');