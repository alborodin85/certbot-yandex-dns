<?php

namespace It5\DebugLibs;

use Throwable;

class DebugLibError extends \Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}