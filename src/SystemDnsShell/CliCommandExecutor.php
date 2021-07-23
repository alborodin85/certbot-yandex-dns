<?php

namespace It5\SystemDnsShell;

use It5\DebugLibs\DebugLib;

class CliCommandExecutor
{
    public function getCommandResultArray(string $command, string $args): array
    {
        return $this->execCommand($command, $args);
    }

    public function getCommandResultString(string $command, string $args): string
    {
        $commandResult = $this->execCommand($command, $args);
        $commandResult = $this->implodeResult($commandResult);

        return $commandResult;
    }

    private function execCommand(string $command, string $args): array
    {
        if ($args) {
            $args = escapeshellarg($args);
            $command .= ' ' . $args;
        }
        $commandResult = [];
        $return_var = 0;
        DebugLib::dump($command);
        exec($command, $commandResult, $return_var);

        if ($return_var) {
            throw new SystemDnsShellError($this->implodeResult($commandResult));
        }

        return $commandResult;
    }

    private function implodeResult(array $commandResult): string
    {
        $commandResult = implode(" ", $commandResult);
        $commandResult = str_replace("\n", '', $commandResult);
        $commandResult = trim($commandResult);

        return $commandResult;
    }
}