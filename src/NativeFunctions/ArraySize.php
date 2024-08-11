<?php

namespace Phortugol\NativeFunctions;

use Phortugol\Interpreter\Interpreter;
use Phortugol\Interpreter\PhortCallable;
use Stringable;

class ArraySize implements PhortCallable, Stringable
{
    public function call(Interpreter $interpreter, array $arguments): mixed
    {
        return count($arguments[0]);
    }

    public function arity(): int
    {
        return 1;
    }

    public function __toString(): string
    {
        return "<fn tamanho>";
    }
}
