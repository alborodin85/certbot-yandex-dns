<?php

namespace It5\SystemDnsShell;

class CliCommandExecutor
{
    public function execCommand(string $command): string
    {
        $commandResult = `{$command}`;
        if (!is_string($commandResult)) {
            $commandResult = '';
        }
        $commandResult = str_replace("\n", '', $commandResult);
        $commandResult = trim($commandResult);

        return $commandResult;
    }
}