<?php

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

    private function __construct()
    {
        //
    }

    function ld($data): void
    {
        if ($this->isQuiet) {
            return;
        }

        echo "\n";
        var_export($data);
        echo "\n";
    }
}