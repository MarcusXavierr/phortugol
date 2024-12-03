<?php

namespace Phortugol\NativeFunctions;

use Phortugol\Interpreter\Interpreter;
use Phortugol\Interpreter\PhortCallable;
use Stringable;

class KeyExists implements PhortCallable, Stringable
{
    public function call(Interpreter $interpreter, array $arguments): mixed
    {
        $array = $arguments[0];
        $key = $arguments[1];

        if (!($array instanceof \Ds\Map)) {
            throw new \RuntimeException("O primeiro argumento deve ser um array");
        }

        return $array->hasKey($key);
    }

    public function arity(): int
    {
        return 2;
    }

    public function __toString(): string
    {
        return "<fn temChave>";
    }
}
