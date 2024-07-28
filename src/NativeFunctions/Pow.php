<?php

namespace Phortugol\NativeFunctions;

use Phortugol\Interpreter\Interpreter;
use Phortugol\Interpreter\PhortCallable;
use Stringable;

class Pow implements PhortCallable, Stringable
{
    public function call(Interpreter $interpreter, array $arguments): mixed
    {
        return pow($arguments[0], $arguments[1]);
    }

    public function arity(): int
    {
        return 2;
    }

    public function __toString(): string
    {
        return "<fn potência>";
    }
}
