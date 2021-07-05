<?php

//xdebug-it5-prod.it5.su

$commandLineParameters = $argv;
$iniParameters = parse_ini_file('settings.ini', true, INI_SCANNER_TYPED);
$isQuiet = in_array('quiet', $commandLineParameters);
$isDryRun = !in_array('quiet', $commandLineParameters);

ld($commandLineParameters);

$commandStr = "sudo certbot certonly --manual-public-ip-logging-ok --agree-tos --email alborodin85@mail.ru --renew-by-default -d *.adm24s-d3-ady.it5.team -d adm24s-d3-ady.it5.team --manual --preferred-challenges dns-01 --server https://acme-v02.api.letsencrypt.org/directory";

$commandStr = "";
$commandStr .= "sudo certbot certonly --manual-public-ip-logging-ok --agree-tos ";


function ld($data): void
{
    global $isQuiet;

    if ($isQuiet) {
        return;
    }

    echo "\n";
    var_export($data);
    echo "\n";
}