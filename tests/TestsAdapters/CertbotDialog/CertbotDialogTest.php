<?php

namespace It5\TestsAdapters\CertbotDialog;

use It5\Adapters\CertbotDialog\CertbotDialog;
use It5\DebugLibs\DebugLib;
use It5\Env;
use It5\ParametersParser\DomainParametersDto;
use PHPUnit\Framework\TestCase;

class CertbotDialogTest extends TestCase
{
    private CertbotDialog $dialogObject;
    private array $arParams = [
        'id' => 1,
        'domain' => 'it5.su',
        'subDomains' => ['dver29spb.ru', '*.dver29spb.ru'],
        'adminEmail' => 'alborodin85@mail.ru',
        'yandexToken' => 'yandexToken',
        'certPath' => '/etc/letsencrypt/live/it5.su/fullchain.pem',
        'certPermissions' => '0750',
        'privKeyPath' => 'privKeyPath',
        'privKeyPermissions' => '0070',

        'criticalRemainingDays' => 7,
        'dnsParameterName' => '_acme-challenge',
        'isDryRun' => true,
        'isForceRenewal' => true,
        'isSudoMode' => true,
    ];

    public function setUp(): void
    {
        DebugLib::init();
        $this->dialogObject = new CertbotDialog();
    }

    public function testStartCheckingAndGetResult()
    {
        $parameters = new DomainParametersDto(...$this->arParams);
        $dialogDto = $this->dialogObject->openDialog($parameters, DebugLib::singleton()->logFile);
        $this->dialogObject->getRequiredDnsRecords($dialogDto, $parameters, count($parameters->subDomains));
        $result = $this->dialogObject->startCheckingAndGetResult($dialogDto);

        $this->assertFalse($result->isOk);
    }

    public function testGetCount()
    {
        $parameters = new DomainParametersDto(...$this->arParams);
        $dialogDto = $this->dialogObject->openDialog($parameters, DebugLib::singleton()->logFile);
        $result = $this->dialogObject->getRequiredDnsRecordsCount($dialogDto, $parameters);

        $this->assertEquals(2, $result);
    }

    public function testDnsParameterError()
    {
        $parameters = new DomainParametersDto(...$this->arParams);
        $correctPattern = Env::env()->certbotCommandPattern;
        Env::env()->certbotCommandPattern = 'ls';
        $dialogDto = $this->dialogObject->openDialog($parameters, DebugLib::singleton()->logFile);
        $this->dialogObject->getRequiredDnsRecords($dialogDto, $parameters, count($parameters->subDomains));
        Env::env()->certbotCommandPattern = $correctPattern;

        $this->assertTrue(true);
    }

    public function testGetRequiredDnsRecords()
    {
        $parameters = new DomainParametersDto(...$this->arParams);
        $dialogDto = $this->dialogObject->openDialog($parameters, DebugLib::singleton()->logFile);
        $result = $this->dialogObject->getRequiredDnsRecords($dialogDto, $parameters, count($parameters->subDomains));
        $this->dialogObject->closeDialog($dialogDto);

        $this->assertArrayHasKey('_acme-challenge', $result[0]);
        $this->assertArrayHasKey('_acme-challenge', $result[1]);


        $this->arParams['subDomains'] = ['cyd-test.dver29spb.ru', '*.cyd-test.dver29spb.ru'];
        $parameters = new DomainParametersDto(...$this->arParams);

        $dialogDto = $this->dialogObject->openDialog($parameters, DebugLib::singleton()->logFile);
        $result = $this->dialogObject->getRequiredDnsRecords($dialogDto, $parameters, count($parameters->subDomains));
        $this->dialogObject->closeDialog($dialogDto);

        $this->assertArrayHasKey('_acme-challenge.cyd-test', $result[0]);
        $this->assertArrayHasKey('_acme-challenge.cyd-test', $result[1]);
    }
}
