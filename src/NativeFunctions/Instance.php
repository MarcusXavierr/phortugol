<?php

namespace Phortugol\NativeFunctions;

use Ds\Map;
use Phortugol\Exceptions\RuntimeError;
use Phortugol\Token;
use Stringable;

class Instance implements Stringable
{
    private PhortClass $phortClass;
    private readonly Map $fields;

    public function __construct(PhortClass $phortClass) {
        $this->phortClass = $phortClass;
        $this->fields = new Map();
    }

    public function get(Token $name): mixed
    {
        if ($this->fields->hasKey($name->lexeme)) {
            return $this->fields->get($name->lexeme);
        }

        $method = $this->phortClass->findMethod($name->lexeme);
        if ($method) {
            if (!($method instanceof PhortugolFunction)) {
                throw new RuntimeError($name, "Era esperado um método com esse nome {$name->lexeme}");
            }

            return $method->bind($this);
        }

        throw new RuntimeError($name, "Propriedade indefinida {$name->lexeme}");
    }

    public function __toString(): string
    {
        return "Instância de {$this->phortClass->name}";
    }

    public function set(Token $token, mixed $value): void
    {
        $this->fields->put($token->lexeme, $value);
    }
}
