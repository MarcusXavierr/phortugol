<?php

namespace Phortugol\NativeFunctions;
use Phortugol\Interpreter\Interpreter;

use Stringable;
use Phortugol\Interpreter\PhortCallable;

class NewVector implements PhortCallable, Stringable {
    public function call(Interpreter $interpreter, array $arguments): mixed
    {
        if (count($arguments) == 1) {
            return new \SplFixedArray($arguments[0]);
        }

        $arr = array_fill(0, $arguments[0], $arguments[1]);
        $vect = new \SplFixedArray($arguments[0]);
        return $vect->fromArray($arr);
    }

    public function arity(): int|array
    {
        return [1, 2];
    }

    public function __toString(): string
    {
        return "fn <vetor>";
    }
}
