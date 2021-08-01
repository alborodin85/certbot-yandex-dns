<?php

namespace It5\Adapters\CertbotDialog;

class DialogResultDto
{
    public function __construct(
        public bool $isOk = false,
        public string $certPath = '',
        public string $privKeyPath = '',
        public string $deadline = '',
    )
    {
    }
}