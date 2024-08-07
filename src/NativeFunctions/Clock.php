<?php

namespace Phortugol\NativeFunctions;

use Phortugol\Interpreter\Interpreter;
use Phortugol\Interpreter\PhortCallable;
use Stringable;

class Clock implements PhortCallable, Stringable
{
    public function call(Interpreter $interpreter, array $arguments): mixed
    {
        return time();
    }

    public function arity(): int
    {
        return 0;
    }

    public function __toString(): string
    {
        return "<fn relógio>";
    }
}
