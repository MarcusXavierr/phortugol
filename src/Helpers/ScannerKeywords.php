<?php

namespace Phortugol\Helpers;

use Phortugol\Enums\TokenType;

class ScannerKeywords
{
    public const KEYWORDS = [
        'E' => TokenType::AND,
        'senao' => TokenType::ELSE,
        'falso' => TokenType::FALSE,
        'repita' => TokenType::FOR,
        'funcao' => TokenType::FUNCTION,
        'função' => TokenType::FUNCTION,
        'se' => TokenType::IF,
        'nulo' => TokenType::NULL,
        'indefinido' => TokenType::UNDEFINED,
        'OU' => TokenType::OR,
        'retorne' => TokenType::RETURN,
        'verdadeiro' => TokenType::TRUE,
        'var' => TokenType::VAR,
        'enquanto' => TokenType::WHILE,
        'pare' => TokenType::BREAK,
        'continue' => TokenType::CONTINUE,
        'escreva' => TokenType::PRINT,
        'senão' => TokenType::ELSE,
        'classe' => TokenType::PCLASS,
        'this' => TokenType::THIS,
        // 'let' => TokenType::LET,
        // 'const' => TokenType::CONST,
    ];
}
