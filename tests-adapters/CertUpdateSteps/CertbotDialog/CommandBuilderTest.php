<?php

namespace CertUpdateSteps\CertbotDialog;

use It5\CertUpdateSteps\CertbotDialog\CommandBuilder;
use It5\DebugLibs\DebugLib;
use It5\ParametersParser\DomainParametersDto;
use PHPUnit\Framework\TestCase;

class CommandBuilderTest extends TestCase
{
    private CommandBuilder $commandBuilder;

    public function setUp(): void
    {
        $this->commandBuilder = new CommandBuilder();
        DebugLib::init('', DebugLib::MODE_WITH_OUTPUT);
    }

    public function testBuildCommandDryRun()
    {
        $commandCorrect = 'sudo certbot certonly --manual-public-ip-logging-ok --agree-tos --email alborodin85@mail.ru --renew-by-default -d *.it5.su -d it5.su --manual --preferred-challenges dns-01 --server https://acme-v02.api.letsencrypt.org/directory --dry-run';

        $parameters = new DomainParametersDto(
            1, 'it5.su', ['*.it5.su', 'it5.su'], 'alborodin85@mail.ru', '', true, '', 0, ''
        );
        $commandResult = $this->commandBuilder->buildCommand($parameters);

        $this->assertEquals($commandCorrect, $commandResult);
    }

    public function testBuildCommandNormal()
    {
        $commandCorrect = 'sudo certbot certonly --manual-public-ip-logging-ok --agree-tos --email vasa@mail.ru --renew-by-default -d *.it5.su -d it5.su --manual --preferred-challenges dns-01 --server https://acme-v02.api.letsencrypt.org/directory ';

        $parameters = new DomainParametersDto(
            1, 'it5.su', ['*.it5.su', 'it5.su'], 'vasa@mail.ru', '', false, '', 0, ''
        );
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
    }
}
