<?php

namespace Phortugol\Exceptions;

use Exception;

class ReturnException extends Exception
{
    public readonly mixed $value;

    public function __construct(mixed $value)
    {
        $this->value = $value;
    }
}
