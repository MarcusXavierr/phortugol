<?php

namespace Phortugol\Scanner;

use Phortugol\Enums\TokenType;
use Phortugol\Helpers\ErrorHelper;
use Phortugol\Helpers\ScannerKeywords;
use Phortugol\Helpers\StringHelper;
use Phortugol\Token;

class LiteralsScanner
{
    private array $source;
    private int $line = 1;
    private ErrorHelper $error;

    /**
     * @param string[] $source
     */
    public function __construct(array $source, ErrorHelper $errorHelper)
    {
        $this->source = $source;
        $this->error = $errorHelper;
    }

    /**
     * @return array{int, ?Token}
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

        $value = StringHelper::arrSubstring($this->source, $start + 1, $current - 1);
        $lexeme = StringHelper::arrSubstring($this->source, $start, $current);
        return [
            $current,
            new Token(TokenType::STRING, $value, $lexeme, $this->line)
        ];
    }
    /**
     * @return array{int, Token}
     */
    public function number(int $start, int $current): array
    {
        $currentIsDigit = fn($current) => !$this->isAtEnd($current) && ctype_digit($this->source[$current]);

        while ($currentIsDigit($current)) {
            $current++;
        }

        // Look for a fractional part.
        if (!$this->isAtEnd($current) && $this->source[$current] === '.' && $currentIsDigit($current + 1)) {
            $current++;

            while ($currentIsDigit($current)) {
                $current++;
            }
        }

        $value = StringHelper::arrSubstring($this->source, $start, $current);
        $lexeme = StringHelper::arrSubstring($this->source, $start, $current);
        return [
            $current,
            new Token(TokenType::NUMBER, (float) $value, $lexeme, $this->line)
        ];
    }
    /**
     * @return array{int, Token}
     */
    public function identifier(int $start, int $current): array
    {
        while (!$this->isAtEnd($current)) {
            $char = $this->source[$current];
            if (!validLexeme($char)) {
                break;
            }
            $current += strlen($char);
        }

        $lexeme = StringHelper::arrSubstring($this->source, $start, $current);
        $kind = ScannerKeywords::KEYWORDS[$lexeme] ?? TokenType::IDENTIFIER;
        return [
            $current,
            new Token($kind, null, $lexeme, $this->line)
        ];
    }

    private function isAtEnd(int $current): bool
    {
        return $current >= count($this->source);
    }
}

function validLexeme(string $str): bool
{
    $pattern = '/^[\p{L}\p{N}]*$/u';
    return preg_match($pattern, $str) ? true : false;
}
