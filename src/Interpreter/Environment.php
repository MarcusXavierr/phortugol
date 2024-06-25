<?php

namespace Phortugol\Interpreter;

use Phortugol\Exceptions\RuntimeError;
use Phortugol\Token;

class Environment
{
    private array $state = [];

    public function define(string $name, mixed $value): void
    {
        $this->state[$name] = $value;
    }

    public function get(Token $name): mixed
    {
        if (array_key_exists($name->lexeme, $this->state)) {
            return $this->state[$name->lexeme];
        }

        throw new RuntimeError($name, "Variável não definida {$name->lexeme}");
    }

    public function assign(Token $name, mixed $value): void
    {
        if (!array_key_exists($name->lexeme, $this->state)) {
            throw new RuntimeError($name, "Variável não definida {$name->lexeme}");
        }

        $this->state[$name->lexeme] = $value;
    }
}
