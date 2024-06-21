<?php

namespace Toyjs\Toyjs\Exceptions;

use Exception;
use Toyjs\Toyjs\Token;

class RuntimeError extends Exception
{
    public readonly Token $token;

    public function __construct(Token $token, string $message)
    {
        parent::__construct($message);
        $this->token = $token;
    }
}
