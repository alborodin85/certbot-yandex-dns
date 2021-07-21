<?php

namespace It5\Adapters\HttpRequestExecutor;

use Throwable;

class HttpRequestExecutorError extends \Error
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}