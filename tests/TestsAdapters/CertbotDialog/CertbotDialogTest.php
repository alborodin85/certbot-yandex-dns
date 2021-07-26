<?php

namespace It5\TestsAdapters\CertbotDialog;

use It5\Adapters\CertbotDialog\CertbotDialog;
use It5\DebugLibs\DebugLib;
use It5\ParametersParser\DomainParametersDto;
use PHPUnit\Framework\TestCase;

class CertbotDialogTest extends TestCase
{
    public function setUp(): void
    {
        DebugLib::init('err.log', DebugLib::MODE_WITH_OUTPUT);
    }

    public function testGetRequiredDnsRecords()
    {
        $dialogObject = new CertbotDialog();

        $arParams = [
            'id' => 1,
            'domain' => 'it5.su',
            'subDomains' => ['it5.su', '*.it5.su'],
            'adminEmail' => 'alborodin85@mail.ru',
            'yandexToken' => 'yandexToken',
            'certPath' => '/etc/letsencrypt/live/it5.su/fullchain.pem',

            'criticalRemainingDays' => 7,
            'dnsParameterName' => '_acme-challenge',
            'isDryRun' => true,
            'isForceRenewal' => true,
            'isSudoMode' => true,
        ];

        $parameters = new DomainParametersDto(...$arParams);

        $dialogDto = $dialogObject->openDialog($parameters, DebugLib::singleton()->logFile);
        $result = $dialogObject->getRequiredDnsRecords($dialogDto, $parameters);
        $dialogObject->closeDialog($dialogDto);

        $this->assertArrayHasKey('_acme-challenge', $result[0]);
        $this->assertArrayHasKey('_acme-challenge', $result[1]);

        $arParams['subDomains'] = ['admin24-ady.it5.su', '*.admin24-ady.it5.su'];
        $parameters = new DomainParametersDto(...$arParams);

        $dialogDto = $dialogObject->openDialog($parameters, DebugLib::singleton()->logFile);
        $result = $dialogObject->getRequiredDnsRecords($dialogDto, $parameters);
        $dialogObject->closeDialog($dialogDto);

        $this->assertArrayHasKey('_acme-challenge.admin24-ady', $result[0]);
        $this->assertArrayHasKey('_acme-challenge.admin24-ady', $result[1]);
    }
}
