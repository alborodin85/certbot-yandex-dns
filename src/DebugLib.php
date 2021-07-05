<?php

namespace It5;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class DebugLib
{
    private static self $selfInstance;

    public static function SO(): self
    {
        if (empty(self::$selfInstance)) {
            self::$selfInstance = new self();
        }

        return self::$selfInstance;
    }

    public bool $isQuiet = false;
    public string $logFile = '';

    private function __construct()
    {
        //
    }

    public function ld(mixed $message, string $level = Logger::DEBUG): bool
    {
        if (!$this->logFile) {
            return false;
        }

        $log = new Logger('name');
        $handler = new StreamHandler($this->logFile, Logger::DEBUG);
        $log->pushHandler($handler);
        $log->log($level, $message);

        return true;
    }

    public function dump(mixed $data): void
    {
        if ($this->isQuiet) {
            return;
        }

        echo "\n";
        var_export($data);
        echo "\n";
    }
}