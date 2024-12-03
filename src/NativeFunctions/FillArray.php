<?php

namespace Phortugol\NativeFunctions;

use Phortugol\Interpreter\Interpreter;
use Phortugol\Interpreter\PhortCallable;
use Stringable;

class FillArray implements PhortCallable, Stringable
{
    public function call(Interpreter $interpreter, array $arguments): mixed
    {
        $size = $arguments[0];
        $value = $arguments[1];
        $pairs = array_map(fn($key) => [(string)$key, $value], range(0, $size - 1));
        $map = new \Ds\Map($pairs);
        return $map;
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
