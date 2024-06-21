<?php

namespace Phortugol\Interpreter;

use Phortugol\Enums\TokenType;
use Phortugol\Exceptions\RuntimeError;
use Phortugol\Token;

class TypeValidator
{
    private array $numericTokenKinds = [
        TokenType::MINUS,
        TokenType::STAR,
        TokenType::SLASH,
        TokenType::MODULO,
        TokenType::GREATER,
        TokenType::GREATER_EQUAL,
        TokenType::LESS,
        TokenType::LESS_EQUAL
    ];

    public function validateIsNumber(Token $operand, mixed ...$values): void
    {
        foreach ($values as $value) {
            if (!is_numeric($value)) {
                throw new RuntimeError($operand, "O operador '{$operand->lexeme}' espera um número");
            }
        }
    }

    public function shouldBeNumeric(TokenType $kind): bool
    {
        return in_array($kind, $this->numericTokenKinds);
    }
}
