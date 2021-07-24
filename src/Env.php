<?php

namespace It5;

class Env
{
    public int $yandexApiDelayMicroseconds = 1 * 1000 * 1000;
    public int $maxWaitingSpreadingSeconds = 60 * 60 * 2;
    public int $testingSpreadingIntervalSeconds = 60;
    public string $googleDnsServerIp = '8.8.8.8';


    private static self $instance;
    public static function env(): self
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}