<?php

namespace It5;

class Env
{
    public int $yandexApiDelayMicroseconds = 1 * 1000 * 1000;
    public int $maxWaitingSpreadingSeconds = 60 * 60 * 3;
//    public int $maxWaitingSpreadingSeconds = 5;
    public int $testingSpreadingIntervalSeconds = 60;
//    public int $testingSpreadingIntervalSeconds = 2;
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