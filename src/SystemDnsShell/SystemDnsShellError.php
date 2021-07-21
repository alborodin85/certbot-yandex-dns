<?php

namespace It5\SystemDnsShell;

use JetBrains\PhpStorm\Pure;

class SystemDnsShellError extends \Exception
{
    #[Pure] public function __construct($message = "", $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}