<?php

namespace Phortugol\NativeFunctions;

use Ds\Map;
use Phortugol\Interpreter\Interpreter;
use Phortugol\Interpreter\PhortCallable;
use RuntimeException;
use Stringable;

class ArrayInsert implements PhortCallable, Stringable
{
    public function call(Interpreter $interpreter, array $arguments): mixed
    {
        $array = $arguments[0];
        $key = $arguments[1];
        $value = $arguments[2];

        if (!($array instanceof Map)) {
            throw new RuntimeException("O primeiro argumento deve ser um array");
        }

        if (!is_scalar($key)) {
            throw new RuntimeException("A chave do array deve ser um número ou uma string");
        }

        $array->put((string)$key, $value);

        return $array;
    }

    public function arity(): int
    {
        return 3;
    }

    public function __toString(): string
    {
        return "<fn inserir>";
    }
}
