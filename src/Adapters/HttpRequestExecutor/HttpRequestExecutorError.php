<?php

namespace It5\Adapters\HttpRequestExecutor;

use JetBrains\PhpStorm\Pure;
use Throwable;

class HttpRequestExecutorError extends \Error
{
    #[Pure] public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
