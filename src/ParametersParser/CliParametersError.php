<?php

namespace It5\ParametersParser;

use JetBrains\PhpStorm\Pure;

class CliParametersError extends \Exception
{
    #[Pure] public function __construct($message = "", $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
