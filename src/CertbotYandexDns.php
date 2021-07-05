<?php

namespace It5;

class CertbotYandexDns
{
    private static self $selfInstance;

    public static function SO(): self
    {
        if (empty(self::$selfInstance)) {
            self::$selfInstance = new self();
        }

        return self::$selfInstance;
    }

    private function __construct()
    {
        //
    }

    public function renewCerts(string $configAbsolutePath, string $logAbsolutePath = '', bool $isQuiet = false): bool
    {

        DebugLib::SO()->logFile = $logAbsolutePath;
        DebugLib::SO()->isQuiet = $isQuiet;
        DebugLib::SO()->dump($configAbsolutePath);
        DebugLib::SO()->ld($configAbsolutePath);

        return true;
    }
}