<?php

namespace Phortugol\Interpreter;

interface PhortCallable
{
    /**
     * @param mixed[] $arguments
     */
    public function call(Interpreter $interpreter, array $arguments): mixed;

    public function arity(): int;

    public function toString(): string;
}
