<?php

namespace Phortugol\NativeFunctions;

use Phortugol\Interpreter\Interpreter;
use Stringable;
use Phortugol\Interpreter\PhortCallable;

class Random implements PhortCallable, Stringable
{
    public function call(Interpreter $interpreter, array $arguments): mixed
    {
        return rand($arguments[0], $arguments[1]);
    }

    public function arity(): int
    {
        return 2;
    }

    public function __toString(): string
    {
        return "<fn aleatório>";
    }
}
