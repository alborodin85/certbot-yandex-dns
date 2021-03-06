<?php

namespace It5\Adapters\CertbotDialog;

use JetBrains\PhpStorm\Pure;
use Throwable;

class CertbotDialogError extends \Error
{
    #[Pure]
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}