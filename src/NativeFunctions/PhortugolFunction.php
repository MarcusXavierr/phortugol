<?php

namespace Phortugol\NativeFunctions;

use Phortugol\Exceptions\ReturnException;
use Phortugol\Interpreter\Environment;
use Phortugol\Interpreter\Interpreter;
use Phortugol\Interpreter\PhortCallable;
use Phortugol\Stmt\FunctionStmt;

class PhortugolFunction implements PhortCallable
{
    private readonly FunctionStmt $declaration;
    private readonly Environment $closure;

    public function __construct(FunctionStmt $declaration, Environment $closure){
        $this->declaration = $declaration;
        $this->closure = $closure;
    }

    public function call(Interpreter $interpreter, array $arguments): mixed
    {
        $environment = new Environment($this->closure);
        for ($i = 0; $i < count($this->declaration->parameters); $i++) {
            // INFO: Just defining values from arguments into variable names defined on parameters
            $environment->define($this->declaration->parameters[$i]->lexeme, $arguments[$i]);
        }

        try {
            $interpreter->executeBlock($this->declaration->body, $environment);
        } catch(ReturnException $returnValue) {
            return $returnValue->value;
        }
        return null;
    }

    public function arity(): int
    {
        return count($this->declaration->parameters);
    }

    public function toString(): string
    {
        return "<fn " . $this->declaration->name->lexeme . ">" ;
    }
}
