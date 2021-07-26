<?php

namespace It5\TestsAdapters\CertbotDialog;

use It5\Adapters\CertbotDialog\CommandBuilder;
use It5\DebugLibs\DebugLib;
use It5\ParametersParser\DomainParametersDto;
use PHPUnit\Framework\TestCase;

class CommandBuilderTest extends TestCase
{
    private CommandBuilder $commandBuilder;
    private array $arParams = [
        'id' => 1,
        'domain' => 'it5.su',
        'subDomains' => ['admin24-ady.it5.su', '*.admin24-ady.it5.su'],
        'adminEmail' => 'vasa@mail.ru',
        'yandexToken' => 'yandexToken',
        'certPath' => '/etc/letsencrypt/live/it5.su/fullchain.pem',

        'criticalRemainingDays' => 7,
        'dnsParameterName' => '_acme-challenge',
        'isDryRun' => true,
        'isForceRenewal' => true,
        'isSudoMode' => true,
    ];

    public function setUp(): void
    {
        $this->commandBuilder = new CommandBuilder();
        DebugLib::init('', DebugLib::MODE_WITH_OUTPUT);
    }

    public function testBuildCommandDryRun()
    {
        $commandCorrect = 'sudo  certbot certonly --manual-public-ip-logging-ok --agree-tos --email vasa@mail.ru --renew-by-default -d admin24-ady.it5.su -d *.admin24-ady.it5.su --manual --preferred-challenges dns-01 --server https://acme-v02.api.letsencrypt.org/directory --dry-run --force-renewal';
        $parameters = new DomainParametersDto(...$this->arParams);
        $commandResult = $this->commandBuilder->buildCommand($parameters);
        $this->assertEquals($commandCorrect, $commandResult);

        $commandCorrect = ' certbot certonly --manual-public-ip-logging-ok --agree-tos --email vasa@mail.ru --renew-by-default -d admin24-ady.it5.su -d *.admin24-ady.it5.su --manual --preferred-challenges dns-01 --server https://acme-v02.api.letsencrypt.org/directory --dry-run --force-renewal';
        $this->arParams['isSudoMode'] = false;
        $parameters = new DomainParametersDto(...$this->arParams);
        $commandResult = $this->commandBuilder->buildCommand($parameters);
        $this->assertEquals($commandCorrect, $commandResult);
    }

    public function testBuildCommandNormal()
    {
        $commandCorrect = 'sudo  certbot certonly --manual-public-ip-logging-ok --agree-tos --email vasa@mail.ru --renew-by-default -d admin24-ady.it5.su -d *.admin24-ady.it5.su --manual --preferred-challenges dns-01 --server https://acme-v02.api.letsencrypt.org/directory  ';
        $this->arParams['isDryRun'] = false;
        $this->arParams['isForceRenewal'] = false;
        $parameters = new DomainParametersDto(...$this->arParams);
        $commandResult = $this->commandBuilder->buildCommand($parameters);
        $this->assertEquals($commandCorrect, $commandResult);
    }

    public function testRetrieveDnsParameterName()
    {
        $stringFromCertbotCli = '- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
Please deploy a DNS TXT record under the name
_acme-challenge.admin24-ady.it5_test2.team with the following value:

if98_xfXE6a6QjruUPK4x6S15PuwKGtRshmEOgC-OFE

Before continuing, verify the record is deployed.';
        $parameters = new DomainParametersDto(
            1, 'it5.team', ['*.it5.team', 'it5.team', '*.admin24-ady.it5.team'], 'alborodin85@mail.ru', '', true, '_acme-challenge', 0, ''
        );

        $dnsParamName = $this->commandBuilder->retrieveDnsParameterName($stringFromCertbotCli, $parameters);

        $this->assertEquals('_acme-challenge.admin24-ady', $dnsParamName);

        $this->expectExceptionMessage('Из диалога с CertBot не удалось извлечь имя dns-параметра!');
        $stringFromCertbotCli = 'vasa';
        $this->commandBuilder->retrieveDnsParameterName($stringFromCertbotCli, $parameters);
    }

    public function testRetrieveDnsParameterValue()
    {
        $stringFromCertbotCli = '- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
Please deploy a DNS TXT record under the name
_acme-challenge.admin24-ady.it5_test2.team with the following value:

if98_xfXE6a6QjruUPK4x6S15PuwKGtRshmEOgC-OFE

Before continuing, verify the record is deployed.';

        $dnsParamName = '_acme-challenge.admin24-ady';
        $dnsParamValue = $this->commandBuilder->retrieveDnsParameterValue($stringFromCertbotCli, $dnsParamName);

        $this->assertEquals('if98_xfXE6a6QjruUPK4x6S15PuwKGtRshmEOgC-OFE', $dnsParamValue);

        $this->expectExceptionMessage('Из диалога с CertBot не удалось извлечь значение dns-параметра!');
        $stringFromCertbotCli = 'vasa';
        $this->commandBuilder->retrieveDnsParameterValue($stringFromCertbotCli, $dnsParamName);
    }

    public function testNotCorrectError()
    {
        $stringFromCertbotCli = '- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
Please deploy a DNS TXT record under the name
_acme-challenge.admin24-ady.it5_test2.team with the following value:

Before continuing, verify the record is deployed.';

        $dnsParamName = '_acme-challenge.admin24-ady';
        $this->expectExceptionMessage('Из диалога с CertBot не удалось извлечь значение dns-параметра!');
        $this->commandBuilder->retrieveDnsParameterValue($stringFromCertbotCli, $dnsParamName);
    }
}
