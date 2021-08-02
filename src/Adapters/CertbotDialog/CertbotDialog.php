<?php

namespace It5\Adapters\CertbotDialog;

use It5\DebugLibs\DebugLib;
use It5\Env;
use It5\ParametersParser\DomainParametersDto;
use JetBrains\PhpStorm\Pure;

class CertbotDialog
{
    private CommandBuilder $commandBuilder;
    private CliAnswersParser $answersParser;
    private string $strLastCertbotAnswer;

    #[Pure]
    public function __construct()
    {
        $this->commandBuilder = new CommandBuilder();
        $this->answersParser = new CliAnswersParser();
    }

    public function openDialog(DomainParametersDto $parametersDto, string $logFile): DialogDto
    {
        $commandPattern = Env::env()->certbotCommandPattern;
        $command = $this->commandBuilder->buildCommand($parametersDto, $commandPattern);

        $spec = [
            ['pipe', 'r'],          // stdin
            ['pipe', 'w'],          // stdout
            ['file', $logFile, 'a'],// stderr
        ];
        $pipes = [];
        $proc = proc_open($command, $spec, $pipes);

        $dialogDto = new DialogDto();
        $dialogDto->process = $proc;
        $dialogDto->stdInPipe = $pipes[0];
        $dialogDto->stdOutPipe = $pipes[1];

        return $dialogDto;
    }

    private function parseDnsRecordsRequestOutput(
        DialogDto $dialogDto, DomainParametersDto $parametersDto, int $countRecords, bool $forceFinish
    ): array
    {
        $dnsRecords = [];
        $fullString = '';
        for ($i = 1; $i <= $countRecords; $i++) {
            DebugLib::ld('Ждем 3 сек, пока Certbot скажет, какую запись добавить...');
            sleep(3);
            $fullString = fread($dialogDto->stdOutPipe, 1024 * 10);

            DebugLib::ld('$fullString');
            DebugLib::ld($fullString);

            try {
                $dnsParamName = $this->answersParser->retrieveDnsParameterName($fullString, $parametersDto);
                $dnsParamValue = $this->answersParser->retrieveDnsParameterValue($fullString, $dnsParamName);
                $dnsRecords[] = [
                    $dnsParamName => $dnsParamValue,
                ];
            } catch (CertbotDialogError) {
                break;
            }

            if (($i != $countRecords) || $forceFinish) {
                fwrite($dialogDto->stdInPipe, "\n");
            }
        }

        $this->strLastCertbotAnswer = $fullString;

        return $dnsRecords;
    }

    public function getRequiredDnsRecordsCount(DialogDto $dialogDto, DomainParametersDto $parametersDto): int
    {
        $dnsRecords = $this
            ->parseDnsRecordsRequestOutput($dialogDto, $parametersDto, count($parametersDto->subDomains), true);
        $dnsRecordsCount = count($dnsRecords);

        return $dnsRecordsCount;
    }

    public function getRequiredDnsRecords(DialogDto $dialogDto, DomainParametersDto $parametersDto, int $countRecords): array
    {
        $dnsRecords = $this
            ->parseDnsRecordsRequestOutput($dialogDto, $parametersDto, $countRecords, false);

        return $dnsRecords;
    }

    public function getResult(): DialogResultDto
    {
        $stringFromCertbotCli = $this->strLastCertbotAnswer;

        $processResult = $this->answersParser->isCertbotResultOk($stringFromCertbotCli);
        // TODO: Переработать, чтобы протестировать строку
        if ($processResult) {
            [$certPath, $privKeyPath, $deadline] = $this->answersParser->retrieveNewCertPath($stringFromCertbotCli);
        }

        $result = new DialogResultDto(
            $processResult, $certPath ?? '', $privKeyPath ?? '', $deadline ?? ''
        );

        return $result;
    }

    public function startCheckingAndGetResult(DialogDto $dialogDto): DialogResultDto
    {
        DebugLib::ld('Отправляем последний Enter...');
        fwrite($dialogDto->stdInPipe, "\n");

        DebugLib::ld('Ждем 10 сек, пока пройдет процесс...');
        sleep(10);
        DebugLib::ld('Принимаем результат вывода...');
        $stringFromCertbotCli = fread($dialogDto->stdOutPipe, 1024 * 10);

        DebugLib::ld('$stringFromCertbotCli-2');
        DebugLib::ld($stringFromCertbotCli);

        $this->strLastCertbotAnswer = $stringFromCertbotCli;

        $result = $this->getResult();

        return $result;
    }

    public function closeDialog(DialogDto $dialogDto): void
    {
        fclose($dialogDto->stdInPipe);
        fclose($dialogDto->stdOutPipe);
        proc_close($dialogDto->process);
    }
}
