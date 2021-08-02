<?php

namespace It5\DebugLibs;

use JetBrains\PhpStorm\Pure;
use Throwable;

class DebugLibError extends \Exception
{
    #[Pure] public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
