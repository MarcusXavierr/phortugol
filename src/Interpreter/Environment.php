<?php

namespace Phortugol\Interpreter;

use Phortugol\Exceptions\RuntimeError;
use Phortugol\Token;

class Environment
{
    public array $state = [];
    public readonly ?Environment $enclosing;

    public function __construct(?Environment $enclosing) {
        $this->enclosing = $enclosing;
    }

    public function define(string $name, mixed $value): void
    {
        $this->state[$name] = $value;
    }

    public function get(Token $name): mixed
    {
        if (array_key_exists($name->lexeme, $this->state)) {
            return $this->state[$name->lexeme];
        }

        if ($this->enclosing != null) {
            return $this->enclosing->get($name);
        }

        throw new RuntimeError($name, "Variável não definida {$name->lexeme}");
    }

    public function assign(Token $name, mixed $value): void
    {
        if (array_key_exists($name->lexeme, $this->state)) {
            $this->state[$name->lexeme] = $value;
            return;
        }

        if ($this->enclosing != null) {
            $this->enclosing->assign($name, $value);
            return;
        }

        throw new RuntimeError($name, "Variável não definida {$name->lexeme}");
    }

    public function getAt(int $distance, string $string): mixed
    {
        return $this->ancestor($distance)->state[$string];
    }

    public function assignAt(int $distance, Token $name, mixed $value): void
    {
        $this->ancestor($distance)->assign($name, $value);
    }

    private function ancestor(int $depth): Environment
    {
        $environment = $this;
        for ($i = 0; $i < $depth; $i++) {
            $environment = $environment->enclosing;
        }

        return $environment;
    }
}
