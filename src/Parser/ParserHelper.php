<?php

namespace Phortugol\Parser;

use Phortugol\Enums\TokenType;
use Phortugol\Exceptions\ParserError;
use Phortugol\Expr\Expr;
use Phortugol\Expr\LambdaExpr;
use Phortugol\Helpers\ErrorHelper;
use Phortugol\Token;

class ParserHelper
{
    private ErrorHelper $errorHelper;
    /** @var Token[] $tokens */
    public array $tokens;
    public int $current;

    /**
     * @param Token[] $tokens
     */
    public function __construct(ErrorHelper $error, array $tokens, int &$current)
    {
        $this->errorHelper = $error;
        $this->tokens = $tokens;
        $this->current = &$current;
    }

    public function validate(TokenType $kind, string $errorMessage): Token
    {
        if ($this->check($kind)) return $this->advance();
        throw $this->error($this->peek(), $errorMessage);
    }

    public function match(TokenType ...$kinds): bool
    {
        foreach ($kinds as $kind) {
            if ($this->check($kind)) {
                $this->current++;
                return true;
            }
        }

        return false;
    }

    public function sincronize(): void
    {
        $this->advance();
        while (!$this->isAtEnd()) {
            if ($this->previous()->kind == TokenType::SEMICOLON) return;

            switch ($this->peek()->kind) {
                case TokenType::FUNCTION:
                case TokenType::VAR:
                case TokenType::FOR:
                case TokenType::IF:
                case TokenType::WHILE:
                case TokenType::PRINT:
                case TokenType::RETURN:
                    return;
            }

            $this->advance();
        }
    }

    public function check(TokenType $kind): bool
    {
        if ($this->isAtEnd()) return false;
        return $this->peek()->kind === $kind;
    }

    public function error(Token $token, string $message): ParserError
    {
        $this->errorHelper->error($token, $message);
        return new ParserError();
    }

    public function peek(): Token
    {
        $sizeTokens = count($this->tokens);
        if ($this->current >= $sizeTokens) {
            $lastLine = $this->tokens[$sizeTokens - 1]->line;
            return new Token(TokenType::EOF, null, "", $lastLine);
        }

        return $this->tokens[$this->current];
    }

    public function advance(): Token
    {
        if (!$this->isAtEnd()) $this->current++;
        return $this->previous();
    }

    public function previous(): Token
    {
        return $this->tokens[max($this->current - 1, 0)];
    }

    public function peekNext(): Token
    {
        if ($this->current + 1 < count($this->tokens)) {
            return $this->tokens[$this->current + 1];
        }

        return $this->tokens[count($this->tokens) - 1];
    }

    public function isAtEnd(): bool
    {
        return $this->peek()->kind === TokenType::EOF;
    }

    public function validateSemicolon(Expr|null $expr, string $errorMessage): void
    {
        if ($expr instanceof LambdaExpr) {
            // INFO: just consume the semicolon as it is not needed
            $this->match(TokenType::SEMICOLON);
            return;
        }

        $this->validate(TokenType::SEMICOLON, $errorMessage);
    }

    // TODO: Refactor this later
    /**
     * Find the right paranthesis then check if it is followed by a lambda return
    */
    public function isLambda(): bool
    {
        $length = count($this->tokens);
        for ($i = $this->current; $i < $length; $i++) {
            if ($this->tokens[$i]->kind == TokenType::RIGHT_PAREN) {
                if (($i + 1) < $length && $this->tokens[$i + 1]->kind == TokenType::LAMBDA_RETURN) {
                    return true;
                }

                return false;
            }
        }

        return false;
    }
}
