<?php
unlink('certbot-yandex-dns.phar');
$phar = new Phar('certbot-yandex-dns.phar');
$phar->startBuffering();
$phar->buildFromDirectory('phar-source');
$phar->setDefaultStub('index.php', 'index.php');
$phar->stopBuffering();
