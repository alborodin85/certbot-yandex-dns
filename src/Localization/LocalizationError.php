<?php

namespace It5\Localization;

use Throwable;

class LocalizationError extends \Error
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}