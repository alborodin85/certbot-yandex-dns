<?php

namespace It5\TestsAdapters\CertbotDialog;

use It5\Adapters\CertbotDialog\CommandBuilder;
use It5\DebugLibs\DebugLib;
use It5\Env;
use It5\ParametersParser\DomainParametersDto;
use PHPUnit\Framework\TestCase;

class CommandBuilderTest extends TestCase
{
    private array $arParams = [
        'id' => 1,
        'domain' => 'it5.su',
        'subDomains' => ['admin24-ady.it5.su', '*.admin24-ady.it5.su'],
        'adminEmail' => 'vasa@mail.ru',
        'yandexToken' => 'yandexToken',
        'certPath' => '/etc/letsencrypt/live/it5.su/fullchain.pem',
        'certPermissions' => '0750',
        'privKeyPath' => '/etc/letsencrypt/live/it5.su/fullchain.pem',
        'privKeyPermissions' => '0070',

        'criticalRemainingDays' => 7,
        'dnsParameterName' => '_acme-challenge',
        'isDryRun' => true,
        'isForceRenewal' => true,
        'isSudoMode' => true,
    ];

    private CommandBuilder $commandBuilder;

    public function setUp(): void
    {
        $this->commandBuilder = new CommandBuilder();
        DebugLib::init();
    }

    public function testBuildCommandDryRun()
    {
        $commandCorrect = 'sudo  certbot certonly --manual-public-ip-logging-ok --agree-tos --email vasa@mail.ru --renew-by-default -d admin24-ady.it5.su -d *.admin24-ady.it5.su --manual --preferred-challenges dns-01 --server https://acme-v02.api.letsencrypt.org/directory --force-interactive --dry-run --force-renewal';
        $parameters = new DomainParametersDto(...$this->arParams);
        $commandResult = $this->commandBuilder->buildCommand($parameters, Env::env()->certbotCommandPattern);
        $this->assertEquals($commandCorrect, $commandResult);

        $commandCorrect = ' certbot certonly --manual-public-ip-logging-ok --agree-tos --email vasa@mail.ru --renew-by-default -d admin24-ady.it5.su -d *.admin24-ady.it5.su --manual --preferred-challenges dns-01 --server https://acme-v02.api.letsencrypt.org/directory --force-interactive --dry-run --force-renewal';
        $this->arParams['isSudoMode'] = false;
        $parameters = new DomainParametersDto(...$this->arParams);
        $commandResult = $this->commandBuilder->buildCommand($parameters, Env::env()->certbotCommandPattern);
        $this->assertEquals($commandCorrect, $commandResult);
    }

    public function testBuildCommandNormal()
    {
        $commandCorrect = 'sudo  certbot certonly --manual-public-ip-logging-ok --agree-tos --email vasa@mail.ru --renew-by-default -d admin24-ady.it5.su -d *.admin24-ady.it5.su --manual --preferred-challenges dns-01 --server https://acme-v02.api.letsencrypt.org/directory --force-interactive  ';
        $this->arParams['isDryRun'] = false;
        $this->arParams['isForceRenewal'] = false;
        $parameters = new DomainParametersDto(...$this->arParams);
        $commandResult = $this->commandBuilder->buildCommand($parameters, Env::env()->certbotCommandPattern);
        $this->assertEquals($commandCorrect, $commandResult);
    }
}
