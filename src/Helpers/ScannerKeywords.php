<?php

namespace Toyjs\Toyjs\Helpers;

use Toyjs\Toyjs\Enums\TokenType;

class ScannerKeywords
{
    public const KEYWORDS = [
        'and' => TokenType::AND,
        'else' => TokenType::ELSE,
        'false' => TokenType::FALSE,
        'for' => TokenType::FOR,
        'function' => TokenType::FUNCTION,
        'if' => TokenType::IF,
        'null' => TokenType::NULL,
        'undefined' => TokenType::UNDEFINED,
        'or' => TokenType::OR,
        'return' => TokenType::RETURN,
        'true' => TokenType::TRUE,
        'var' => TokenType::VAR,
        'while' => TokenType::WHILE,
        'break' => TokenType::BREAK,
        'continue' => TokenType::CONTINUE,
        'let' => TokenType::LET,
        'const' => TokenType::CONST,
    ];
}
