<?php

// CLI-параметры: '--quiet'

require_once __DIR__ . '/vendor/autoload.php';

use It5\CertbotYandexDns;

$parameters = [
    'cliArgv' => $argv,
    'configAbsolutePath' => __DIR__ . '/settings-it5.team.json',
    'logAbsolutePath' => __DIR__ . '/app.log',
];

CertbotYandexDns::singleton(...$parameters)->renewCerts();
