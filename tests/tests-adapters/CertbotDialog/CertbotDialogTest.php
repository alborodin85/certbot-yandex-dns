<?php

namespace CertUpdateSteps\CertbotDialog;

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

        $parameters = new DomainParametersDto(
            1, 'it5.su', ['it5.su', '*.it5.su'], 'alborodin85@mail.ru', '', true, '_acme-challenge', 7, '/etc/letsencrypt/live/it5.su/fullchain.pem'
        );

        $dialogDto = $dialogObject->openDialog($parameters, DebugLib::singleton()->logFile);
        $result = $dialogObject->getRequiredDnsRecords($dialogDto, $parameters);
        $dialogObject->closeDialog($dialogDto);

        $this->assertArrayHasKey('_acme-challenge', $result[0]);
        $this->assertArrayHasKey('_acme-challenge', $result[1]);

        $parameters = new DomainParametersDto(
            1, 'it5.su', ['admin24-ady.it5.su', '*.admin24-ady.it5.su'], 'alborodin85@mail.ru', '', true, '_acme-challenge', 7, '/etc/letsencrypt/live/it5.su/fullchain.pem'
        );

        $dialogDto = $dialogObject->openDialog($parameters, DebugLib::singleton()->logFile);
        $result = $dialogObject->getRequiredDnsRecords($dialogDto, $parameters);
        $dialogObject->closeDialog($dialogDto);

        $this->assertArrayHasKey('_acme-challenge.admin24-ady', $result[0]);
        $this->assertArrayHasKey('_acme-challenge.admin24-ady', $result[1]);
    }
}
