<?php

namespace Toyjs\Toyjs\Scanner;

use Toyjs\Toyjs\Enums\TokenType;
use Toyjs\Toyjs\Helpers\ErrorHelper;
use Toyjs\Toyjs\Helpers\ScannerKeywords;
use Toyjs\Toyjs\Helpers\StringHelper;
use Toyjs\Toyjs\Token;

class LiteralsScanner
{
    private string $source;
    private int $line = 1;
    private ErrorHelper $error;

    public function __construct(string $source, ErrorHelper $errorHelper)
    {
        $this->source = $source;
        $this->error = $errorHelper;
    }

    /**
     * @return array<int, Token>
     */
    public function string(int $start, int $current, string $delimiter): array
    {
        while (!$this->isAtEnd($current) && $this->source[$current] !== $delimiter) {
            if ($this->source[$current] === "\n") {
                $this->line++;
            }

            $current++;
        }

        if ($this->isAtEnd($current)) {
            $this->error->report($this->line, 'Unterminated string.');
            return [ $current, null ];
        }

        // The closing ".
        $current++;

        $value = StringHelper::substring($this->source, $start + 1, $current - 1);
        $lexeme = StringHelper::substring($this->source, $start, $current);
        return [
            $current,
            new Token(TokenType::STRING, $value, $lexeme, $this->line)
        ];
    }
    /**
     * @return array<int,array<int, Token>>
     */
    public function number(int $start, int $current): array
    {
        $currentIsDigit = fn($current) => !$this->isAtEnd($current) && ctype_digit($this->source[$current]);

        while ($currentIsDigit($current)) {
            $current++;
        }

        // Look for a fractional part.
        if ($this->source[$current] === '.' && $currentIsDigit($current + 1)) {
            $current++;

            while ($currentIsDigit($current)) {
                $current++;
            }
        }

        $value = StringHelper::substring($this->source, $start, $current);
        $lexeme = StringHelper::substring($this->source, $start, $current);
        return [
            $current,
            new Token(TokenType::NUMBER, (float) $value, $lexeme, $this->line)
        ];
    }
    /**
     * @return array<int,mixed>
     */
    public function identifier(int $start, int $current): array
    {
        while (!$this->isAtEnd($current)) {
            if (!ctype_alnum($this->source[$current]) && $this->source[$current] !== '_') {
                break;
            }
            $current++;
        }

        // TODO: Validate why console.log returns IDENTIFIER IDENTIFIER DOT instead of IDENTIFIER DOT IDENTIFIER
        $lexeme = StringHelper::substring($this->source, $start, $current);
        $kind = ScannerKeywords::KEYWORDS[$lexeme] ?? TokenType::IDENTIFIER;
        return [
            $current,
            new Token($kind, null, $lexeme, $this->line)
        ];
    }

    private function isAtEnd(int $current): bool
    {
        return $current >= strlen($this->source);
    }
}
