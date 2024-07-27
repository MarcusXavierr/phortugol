<?php

namespace Phortugol\NativeFunctions;

use Phortugol\Interpreter\Interpreter;
use Phortugol\Interpreter\PhortCallable;

class Clock implements PhortCallable
{
    public function call(Interpreter $interpreter, array $arguments): mixed
    {
        return time();
    }

    public function arity(): int
    {
        return 0;
    }

    public function toString(): string
    {
        return "<native fn>";
    }
}
