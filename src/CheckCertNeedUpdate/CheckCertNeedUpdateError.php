<?php

namespace It5\CheckCertNeedUpdate;

use Throwable;

class CheckCertNeedUpdateError extends \Error
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

}