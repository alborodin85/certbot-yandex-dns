<?php

require_once (__DIR__ . '/vendor/autoload.php');

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$log = new Logger('name');
$handler = new StreamHandler('app.log', Logger::NOTICE);
$log->pushHandler($handler);

$log->warning('Предупрежа');