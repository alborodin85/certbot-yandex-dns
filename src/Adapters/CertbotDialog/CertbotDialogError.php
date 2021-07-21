<?php

namespace It5\CertUpdateSteps\CertbotDialog;

use JetBrains\PhpStorm\Pure;
use Throwable;

class CertbotDialogError extends \Exception
{
    #[Pure]
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}