<?php

// CLI-параметры: '--quiet'

require_once __DIR__ . '/vendor/autoload.php';

use It5\CertbotYandexDns;

$parameters = [
    'cliArgv' => $argv,
    'configAbsolutePath' => 'settings.json',
    'logAbsolutePath' => 'app.log',
];

CertbotYandexDns::singleton(...$parameters)->renewCerts();
