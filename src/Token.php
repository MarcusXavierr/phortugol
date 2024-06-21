<?php

namespace Phortugol;

use Phortugol\Enums\TokenType;

class Token
{
    public readonly TokenType $kind;
    // Just used for literal tokens such as strings, numbers, etc.
    public readonly mixed $literal;
    // The actual text of the token.
    public readonly string $lexeme;
    // The line number where the token was found.
    public readonly int $line;

    public function __construct(TokenType $kind, mixed $literal, string $lexeme, int $line)
    {
        $this->kind = $kind;
        $this->literal = $literal;
        $this->lexeme = $lexeme;
        $this->line = $line;
    }

    public function toString(): string
    {
        return "{$this->kind->name} {$this->lexeme} {$this->literal}";
    }
}
