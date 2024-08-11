<?php

namespace Phortugol\NativeFunctions;

use Ds\Map;
use Phortugol\Interpreter\Interpreter;
use Phortugol\Interpreter\PhortCallable;
use RuntimeException;
use Stringable;

class ArrayPush implements PhortCallable, Stringable
{
    public function call(Interpreter $interpreter, array $arguments): mixed
    {
        $array = $arguments[0];
        $value = $arguments[1];

        if (!($array instanceof Map)) {
            throw new RuntimeException("O primeiro argumento deve ser um array");
        }

        $array->put($array->count(), $value);

        return $array;
    }

    public function arity(): int
    {
        return 2;
    }

    public function __toString(): string
    {
        return "<fn inserir>";
    }
}
