<?php

namespace It5;

class Env
{
    public int $yandexApiDelayMicroseconds = 1 * 1000 * 1000;
    public int $maxWaitingSpreadingSeconds = 60 * 60 * 2;
    public int $testingSpreadingIntervalSeconds = 60;
    public string $googleDnsServerIp = '8.8.8.8';
    public int $additionalWaitingSecs = 60;
    public string $certbotCommandPattern = '%s certbot certonly --manual-public-ip-logging-ok --agree-tos --email %s --renew-by-default %s --manual --preferred-challenges dns-01 --server https://acme-v02.api.letsencrypt.org/directory --force-interactive %s %s';
    public string $dnsParameterName = '_acme-challenge';

    private static self $instance;
    public static function env(): self
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
