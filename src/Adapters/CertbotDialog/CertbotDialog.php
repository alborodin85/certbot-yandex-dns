<?php

namespace It5\Adapters\CertbotDialog;

use It5\DebugLibs\DebugLib;
use It5\ParametersParser\DomainParametersDto;
use JetBrains\PhpStorm\Pure;

class CertbotDialog
{
    private CommandBuilder $commandBuilder;

    #[Pure]
    public function __construct()
    {
        $this->commandBuilder = new CommandBuilder();
    }

    public function openDialog(DomainParametersDto $parametersDto, string $logFile): DialogDto
    {
        $command = $this->commandBuilder->buildCommand($parametersDto);

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

    public function getRequiredDnsRecords(DialogDto $dialogDto, DomainParametersDto $parametersDto): array
    {
        $dnsRecords = [];
        for ($i = 1; $i <= count($parametersDto->subDomains); $i++) {
            $endString = 'Before continuing, verify the record is deployed.';
            $currString = '';
            $fullString = '';
            while (!str_contains($currString, $endString)) {
                $currString = fgets($dialogDto->stdOutPipe);
                $currString = trim($currString);
                $fullString .= $currString . "\n";
            }
            $dnsParamName = $this->commandBuilder->retrieveDnsParameterName($fullString, $parametersDto);
            $dnsParamValue = $this->commandBuilder->retrieveDnsParameterValue($fullString, $dnsParamName);
            $dnsRecords[] = [
                $dnsParamName => $dnsParamValue,
            ];
            if ($i != count($parametersDto->subDomains)) {
                fwrite($dialogDto->stdInPipe, "\n");
            } else {
                break;
            }
        }

        return $dnsRecords;
    }

    public function startCheckingAndGetResult(DialogDto $dialogDto)
    {
        fwrite($dialogDto->stdInPipe, "\n");
        $strCheckingResult = '';
        $startTime = time();
        while (time() < ($startTime + 10)) {
            $currString = fgets($dialogDto->stdOutPipe);
            DebugLib::ld($currString);
            $strCheckingResult .= $currString;
        }
        DebugLib::ld('$strCheckingResult');
        DebugLib::ld($strCheckingResult);
    }

    public function closeDialog(DialogDto $dialogDto): void
    {
        fclose($dialogDto->stdInPipe);
        fclose($dialogDto->stdOutPipe);
        proc_close($dialogDto->process);
    }
}