<?php

namespace It5\TestsAdapters\CertbotDialog;

use It5\Adapters\CertbotDialog\CertbotDialogError;
use It5\Adapters\CertbotDialog\CliAnswersParser;
use It5\DebugLibs\DebugLib;
use It5\ParametersParser\DomainParametersDto;
use PHPUnit\Framework\TestCase;

class CliAnswersParserTest extends TestCase
{
    private CliAnswersParser $answersParser;

    public function setUp(): void
    {
        DebugLib::init();
        $this->answersParser = new CliAnswersParser();
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

        $dnsParamName = $this->answersParser->retrieveDnsParameterName($stringFromCertbotCli, $parameters);

        $this->assertEquals('_acme-challenge.admin24-ady', $dnsParamName);

        $this->expectExceptionMessage('Из диалога с CertBot не удалось извлечь имя dns-параметра!');
        $stringFromCertbotCli = 'vasa';
        $this->answersParser->retrieveDnsParameterName($stringFromCertbotCli, $parameters);
    }

    private function testValue(string $stringFromCertbotCli)
    {

    }

    public function testNotEndsOfString() {

        $stringFromCertbotCli = '- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - Please deploy a DNS TXT record under the name _acme-challenge.admin24-ady.it5_test2.team with the following value:  if98_xfXE6a6QjruUPK4x6S15PuwKGtRshmEOgC-OFE  Before continuing, verify the record is deployed.';

        $dnsParamName = '_acme-challenge.admin24-ady.it5_test2.team';
        $dnsParamValue = $this->answersParser->retrieveDnsParameterValue($stringFromCertbotCli, $dnsParamName);

        $this->assertEquals('if98_xfXE6a6QjruUPK4x6S15PuwKGtRshmEOgC-OFE', $dnsParamValue);

        $this->expectExceptionMessage('Из диалога с CertBot не удалось извлечь значение dns-параметра!');
        $stringFromCertbotCli = 'vasa';
        $this->answersParser->retrieveDnsParameterValue($stringFromCertbotCli, $dnsParamName);
    }


    public function testRetrieveDnsParameterValue()
    {
        $stringFromCertbotCli = '- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
Please deploy a DNS TXT record under the name
_acme-challenge.admin24-ady.it5_test2.team with the following value:

if98_xfXE6a6QjruUPK4x6S15PuwKGtRshmEOgC-OFE

Before continuing, verify the record is deployed.';

        $dnsParamName = '_acme-challenge.admin24-ady.it5_test2.team';
        $dnsParamValue = $this->answersParser->retrieveDnsParameterValue($stringFromCertbotCli, $dnsParamName);

        $this->assertEquals('if98_xfXE6a6QjruUPK4x6S15PuwKGtRshmEOgC-OFE', $dnsParamValue);

        $this->expectExceptionMessage('Из диалога с CertBot не удалось извлечь значение dns-параметра!');
        $stringFromCertbotCli = 'vasa';
        $this->answersParser->retrieveDnsParameterValue($stringFromCertbotCli, $dnsParamName);
    }

    public function testNotCorrectError()
    {
        $stringFromCertbotCli = '- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
Please deploy a DNS TXT record under the name
_acme-challenge.admin24-ady.it5_test2.team with the following value:

Before continuing, verify the record is deployed.';

        $dnsParamName = '_acme-challenge.admin24-ady';
        $this->expectExceptionMessage('Из диалога с CertBot не удалось извлечь значение dns-параметра!');
        $this->answersParser->retrieveDnsParameterValue($stringFromCertbotCli, $dnsParamName);
    }

    public function testParseNewCertPathOk()
    {
        $certPath = '/etc/letsencrypt/live/it5.team/fullchain.pem';
        $privKeyPath = '/etc/letsencrypt/live/it5.team/privkey.pem';
        $deadline = '2021-10-28';
        $stringFromCertbotCli = "IMPORTANT NOTES:  - Congratulations! Your certificate and chain have been saved at:    {$certPath}    Your key file has been saved at:    {$privKeyPath}    Your cert will expire on {$deadline}. To obtain a new or tweaked    version of this certificate in the future, simply run certbot    again. To non-interactively renew *all* of your certificates, run    \"certbot renew\"  - If you like Certbot, please consider supporting our work by:     Donating to ISRG / Let's Encrypt:   https://letsencrypt.org/donate    Donating to EFF:                    https://eff.org/donate-le";

        [$certPathFact, $privKeyPathFact, $deadlineFact] = $this->answersParser->retrieveNewCertPath($stringFromCertbotCli);

        $this->assertEquals($certPath, $certPathFact);
        $this->assertEquals($privKeyPath, $privKeyPathFact);
        $this->assertEquals($deadline, $deadlineFact);
    }

    public function testParseNewCertPathError()
    {
        $stringFromCertbotCli = "IMPORTANT NOTES:  - The following errors were reported by the server:     Domain: admin24-ady-d2.it5.team    Type:   unauthorized    Detail: No TXT record found at    _acme-challenge.admin24-ady-d2.it5.team     Domain: it5.team    Type:   unauthorized    Detail: Incorrect TXT record    \"WHK_xeP2PJ88CILcVx4MBzuR2oKHNiceoA2nYEwIMt0\" (and 1 more) found at    _acme-challenge.it5.team     Domain: it5.team    Type:   unauthorized    Detail: Incorrect TXT record    \"4Sos2VHJylByW54lyK67eid8NvPmXrv5cBmEchghvFs\" (and 1 more) found at    _acme-challenge.it5.team     To fix these errors, please make sure that your domain name was    entered correctly and the DNS A/AAAA record(s) for that domain    contain(s) the right IP address.";

        $this->expectException(CertbotDialogError::class);
        $this->answersParser->retrieveNewCertPath($stringFromCertbotCli);
    }

    public function testCertResultError()
    {
        $stringFromCertbotCli = "IMPORTANT NOTES:  - The following errors were reported by the server:     Domain: admin24-ady-d2.it5.team    Type:   unauthorized    Detail: No TXT record found at    _acme-challenge.admin24-ady-d2.it5.team     Domain: it5.team    Type:   unauthorized    Detail: Incorrect TXT record    \"WHK_xeP2PJ88CILcVx4MBzuR2oKHNiceoA2nYEwIMt0\" (and 1 more) found at    _acme-challenge.it5.team     Domain: it5.team    Type:   unauthorized    Detail: Incorrect TXT record    \"4Sos2VHJylByW54lyK67eid8NvPmXrv5cBmEchghvFs\" (and 1 more) found at    _acme-challenge.it5.team     To fix these errors, please make sure that your domain name was    entered correctly and the DNS A/AAAA record(s) for that domain    contain(s) the right IP address.";

        $result = $this->answersParser->isCertbotResultOk($stringFromCertbotCli);

        $this->assertFalse($result);
    }

    public function testCertResultOkDryRun()
    {
        $stringFromCertbotCli = "IMPORTANT NOTES:  - The dry run was successful.";

        $result = $this->answersParser->isCertbotResultOk($stringFromCertbotCli);

        $this->assertTrue($result);
    }

    public function testCertResultOkNormal()
    {

        $stringFromCertbotCli = "IMPORTANT NOTES:  - Congratulations! Your certificate and chain have been saved at:    /etc/letsencrypt/live/it5.team/fullchain.pem    Your key file has been saved at:    /etc/letsencrypt/live/it5.team/privkey.pem    Your cert will expire on 2021-10-28. To obtain a new or tweaked    version of this certificate in the future, simply run certbot    again. To non-interactively renew *all* of your certificates, run    \"certbot renew\"  - If you like Certbot, please consider supporting our work by:     Donating to ISRG / Let's Encrypt:   https://letsencrypt.org/donate    Donating to EFF:                    https://eff.org/donate-le ";

        $result = $this->answersParser->isCertbotResultOk($stringFromCertbotCli);

        $this->assertTrue($result);
    }
}
