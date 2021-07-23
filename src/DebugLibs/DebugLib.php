<?php

namespace It5\DebugLibs;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class DebugLib
{
    /** @var int - Ни в консоль, ни в лог */
    const MODE_QUIET = 1;
    /** @var int - Только в лог; консоль чистая */
    const MODE_LOG_ONLY = 2;
    /** @var int - И в лог, и в консоль */
    const MODE_WITH_OUTPUT = 3;

    private static self | null $instance;

    public static function init(
        string $logFile = __DIR__ . 'log-path-need-define.log',
        int $mode = self::MODE_WITH_OUTPUT
    ): self {
        self::$instance = new self($logFile, $mode);

        return self::$instance;
    }

    public static function singleton(): self
    {
        if (empty(self::$instance)) {
            throw new DebugLibError('DebugLib not initialized!');
        }

        return self::$instance;
    }

    public static function reset(): void
    {
        self::$instance = null;
    }

    private function __construct(
        public string $logFile,
        public int $mode,
    )
    {
        //
    }

    public static function ld(mixed $message, string $level = Logger::DEBUG): bool
    {
        return self::singleton()->instanceLog($message, $level);
    }

    public function instanceLog(mixed $message, string $level = Logger::DEBUG): bool
    {
        if ($this->mode < 2) {
            return false;
        }
        if (!$this->logFile) {
            throw new DebugLibError('DebugLib::logFile not defined!');
        }

        $log = new Logger('name');
        $handler = new StreamHandler($this->logFile, Logger::DEBUG);
        $log->pushHandler($handler);
        $log->log($level, $message);

        return true;
    }

    public static function dump(mixed $data): bool
    {
        return self::singleton()->instanceDump($data);
    }

    public function instanceDump(mixed $data): bool
    {
        if ($this->mode < 3) {
            return false;
        }

        echo "\n";
        var_export($data);
        echo "\n";

        return true;
    }
}