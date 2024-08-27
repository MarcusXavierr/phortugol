<?php

namespace Phortugol\NativeFunctions;

use Ds\Map;
use Phortugol\Enums\TokenType;
use Phortugol\Interpreter\Interpreter;
use Phortugol\Interpreter\PhortCallable;
use Stringable;

class PhortClass implements PhortCallable, Stringable
{
    public readonly string $name;
    public readonly Map $methods;

    /**
     * @param Map<mixed,PhortCallable> $methods
     */
    public function __construct(string $name, Map $methods)
    {
        $this->name = $name;
        $this->methods = $methods;
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function call(Interpreter $interpreter, array $arguments): mixed
    {
        $instance = new Instance($this);

        $initializer = $this->findMethod(TokenType::CONSTRUCTOR->value);
        if ($initializer) {
            $initializer->bind($instance)->call($interpreter, $arguments);
        }

        return $instance;
    }

    public function arity(): int
    {
        $initializer = $this->findMethod(TokenType::CONSTRUCTOR->value);
        if (!$initializer) {
            return 0;
        }

        return $initializer->arity();
    }

    public function findMethod(string $name): mixed
    {
        return $this->methods->get($name, null);
    }
}
