<?php

namespace It5\CheckCertNeedUpdate;

use JetBrains\PhpStorm\Pure;
use Throwable;

class CheckCertNeedUpdateError extends \Error
{
    #[Pure] public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

}
