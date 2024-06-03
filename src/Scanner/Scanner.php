<?php

namespace Toyjs\Toyjs\Scanner;

use Toyjs\Toyjs\Enums\TokenType;
use Toyjs\Toyjs\Helpers\ErrorHelper;
use Toyjs\Toyjs\Helpers\ScannerKeywords;
use Toyjs\Toyjs\Helpers\StringHelper;
use Toyjs\Toyjs\Token;

// TODO: Add tests for this class and then refactor it
class Scanner
{
    private readonly string $source;
    private array $tokens = [];
    private int $start = 0;
    private int $current = 0;
    private int $line = 1;
    private ErrorHelper $error;

    public function __construct(string $source, ErrorHelper $errorHelper)
    {
        $this->source = $source;
        $this->error = $errorHelper;
    }

    /**
     * @return Token[]
     */
    public function scanTokens(): array
    {
        while (!$this->isAtEnd()) {
            // We are at the beginning of the next lexeme.
            $this->start = $this->current;
            $this->scanToken();
        }

        $this->tokens[] = new Token(TokenType::EOF, null, '', $this->line);
        return $this->tokens;
    }

    private function scanToken(): void
    {
        $char = $this->advance();

        match($char) {
            // Single char token
            '(' => $this->addToken(TokenType::LEFT_PAREN),
            ')' => $this->addToken(TokenType::RIGHT_PAREN),
            '{' => $this->addToken(TokenType::LEFT_BRACE),
            '}' => $this->addToken(TokenType::RIGHT_BRACE),
            '*' => $this->addToken(TokenType::STAR),
            ',' => $this->addToken(TokenType::COMMA),
            '.' => $this->addToken(TokenType::DOT),
            ';' => $this->addToken(TokenType::SEMICOLON),
            '%' => $this->addToken(TokenType::MODULO),

            // One or two char tokens
            '+' => $this->addToken($this->match('+') ? TokenType::PLUS_PLUS: TokenType::PLUS),
            '-' => $this->addToken($this->match('-') ? TokenType::MINUS_MINUS: TokenType::MINUS),
            '=' => $this->addToken($this->match('=') ? TokenType::EQUAL_EQUAL: TokenType::EQUAL),
            '>' => $this->addToken($this->match('=') ? TokenType::GREATER_EQUAL: TokenType::GREATER),
            '<' => $this->addToken($this->match('=') ? TokenType::LESS_EQUAL: TokenType::LESS),
            '!' => $this->addToken($this->match('=') ? TokenType::BANG_EQUAL: TokenType::BANG),

            //special cases
            '\n' => (fn () => $this->line++),
            ' ' => (function(){}), // do nothing
            '\t' => (function(){}), // do nothing
            '\r' => (function(){}), // do nothing

            // TODO: Add support for multiline comments
            // Maybe it's a comment
            '/' => (function() {
                if ($this->match('/')) {
                    $this->singleLineComment();
                } else {
                    $this->addToken(TokenType::SLASH);
                }
            })(),

            // Literals and identifiers
            '"' => $this->string('"'),
            '\'' => $this->string('\''),

            default => (function() use($char) {
                if (ctype_digit($char)) {
                    $this->number();
                } else if (ctype_alpha($char)) {
                    $this->identifier();
                } else {
                    $this->error->report($this->line, "Unexpected character.");
                }
            })()
        };
    }

    private function string(string $stringSeparator): void
    {
        while($this->peek() != $stringSeparator && !$this->isAtEnd()) {
            if ($this->peek() == '\n') {
                $this->line++;
            }
            $this->current++;
        }

        if ($this->isAtEnd()) {
            $this->error->report($this->line, "End of string expected.");
            return;
        }

        // the closing "
        $this->current++;
        $literal = StringHelper::substring($this->source, $this->start + 1, $this->current - 1);
        $lexeme = StringHelper::substring($this->source, $this->start, $this->current);
        $this->tokens[] = new Token(TokenType::STRING, $literal, $lexeme, $this->line);
    }

    private function number(): void
    {
        $currentIsDigit = fn() => ctype_digit($this->peek());

        while ($currentIsDigit()) $this->advance();

        // If number is 3.1415, match the numbers after '.' too
        if ($this->match('.') && $currentIsDigit()) {
            while ($currentIsDigit()) $this->advance();
        }

        $lexeme = StringHelper::substring($this->source, $this->start, $this->current);
        $num = floatval($lexeme);
        $this->tokens[] = new Token(TokenType::NUMBER, $num, $lexeme, $this->line);
    }

    private function identifier(): void
    {
        while(ctype_alnum($this->peek()) || $this->peek() == '_') {
            $this->advance();
        }

        // TODO: Validate why console.log returns IDENTIFIER IDENTIFIER DOT instead of IDENTIFIER DOT IDENTIFIER
        $lexeme = StringHelper::substring($this->source, $this->start, $this->current);
        $kind = ScannerKeywords::KEYWORDS[$lexeme] ?? TokenType::IDENTIFIER;
        $this->tokens[] = new Token($kind, null, $lexeme, $this->line);
    }

    private function singleLineComment(): void
    {
        while(!$this->isAtEnd() && $this->peek() != '\n') {
            // Advance until comment is gone
            $this->advance();
        }
    }

    private function addToken(TokenType $kind): void
    {
        $this->pushToken($kind, null);
    }

    private function pushToken(TokenType $kind, mixed $literal): void
    {
        $lexemeSize = $this->current - $this->start;
        $lexeme = substr($this->source, $this->start, $lexemeSize);

        $this->tokens[] = new Token($kind, $literal, $lexeme, $this->line);
    }

    private function match(string $expected): bool
    {
        if ($this->isAtEnd()) return false;
        if ($this->peek() != $expected) return false;

        $this->current++;
        return true;
    }

    private function peek(): string
    {
        if ($this->isAtEnd()) return '\0';
        return $this->source[$this->current];
    }

    private function advance(): string
    {
        $char = $this->source[$this->current];
        $this->current++;
        return $char;
    }

    private function isAtEnd(): bool
    {
        return $this->current >= strlen($this->source);
    }
}
