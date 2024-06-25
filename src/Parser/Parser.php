<?php

namespace Phortugol\Parser;

use Phortugol\Enums\TokenType;
use Phortugol\Exceptions\ParserError;
use Phortugol\Expr\BinaryExpr;
use Phortugol\Expr\ConditionalExpr;
use Phortugol\Expr\Expr;
use Phortugol\Expr\GroupingExpr;
use Phortugol\Expr\LiteralExpr;
use Phortugol\Expr\UnaryExpr;
use Phortugol\Helpers\ErrorHelper;
use Phortugol\Stmt\ExpressionStmt;
use Phortugol\Stmt\PrintStmt;
use Phortugol\Stmt\Stmt;
use Phortugol\Token;

class Parser
{
    private ErrorHelper $errorHelper;
    /** @var Token[] $tokens */
    private array $tokens;
    private int $current = 0;

    /**
     * @param Token[] $tokens
     */
    public function __construct(ErrorHelper $error, array $tokens) {
        $this->errorHelper = $error;
        $this->tokens = $tokens;
    }

    /**
    * @return Stmt[]|null
    */
    public function parse(): array | null
    {
        $statements = [];
        try {
            while(!$this->isAtEnd()) {
                array_push($statements, $this->statement());
            }
            return $statements;
        } catch (ParserError $e) {
            return null;
        }
    }

    private function statement(): Stmt
    {
        if ($this->match(TokenType::PRINT)) return $this->printStmt();

        return $this->expressionStmt();
    }

    private function expressionStmt(): Stmt
    {
        $expr = $this->expression();
        $this->validate(TokenType::SEMICOLON, "É esperado um ';' no fim da expressão");
        return new ExpressionStmt($expr);
    }

    // TODO: Parse print with parenthesis
    private function printStmt(): Stmt
    {
        $expr = $this->expression();
        $this->validate(TokenType::SEMICOLON, "É esperado um ';' no fim da expressão");
        return new PrintStmt($expr);
    }

    private function expression(): Expr
    {
        return $this->conditional();
    }

    private function conditional(): Expr
    {
        $expr = $this->logic_or();

        if ($this->match(TokenType::QUESTION)) {
            $left = $this->expression();

            if (!$this->match(TokenType::COLON)) {
                throw $this->error($this->peek(), "É esperado um ':' e a expressão caso seja falso.");
            }
            $right = $this->conditional();
            $expr = new ConditionalExpr($expr, $left, $right);
        }

        return $expr;
    }

    private function logic_or(): Expr
    {
        $expr = $this->logic_and();

        while($this->match(TokenType::OR)) {
            $operator = $this->previous();
            $right = $this->logic_and();

            $expr = new BinaryExpr($expr, $operator, $right);
        }

        return $expr;
    }

    private function logic_and(): Expr
    {
        $expr = $this->equality();

        while($this->match(TokenType::AND)) {
            $operator = $this->previous();
            $right = $this->equality();

            $expr = new BinaryExpr($expr, $operator, $right);
        }

        return $expr;
    }

    private function equality(): Expr
    {
        $expr = $this->comparison();
        while($this->match(TokenType::BANG_EQUAL, TokenType::EQUAL_EQUAL)) {
            $operator = $this->previous();
            $right = $this->comparison();
            $expr = new BinaryExpr($expr, $operator, $right);
        }

        return $expr;
    }

    private function comparison(): Expr
    {
        $expr = $this->term();

        while($this->match(TokenType::GREATER, TokenType::GREATER_EQUAL, TokenType::LESS, TokenType::LESS_EQUAL)) {
            $operator = $this->previous();
            $right = $this->term();
            $expr = new BinaryExpr($expr, $operator, $right);
        }

        return $expr;
    }

    private function term(): Expr
    {
        $expr = $this->factor();

        while($this->match(TokenType::MINUS, TokenType::PLUS)) {
            $operator = $this->previous();
            $right = $this->factor();
            $expr = new BinaryExpr($expr, $operator, $right);
        }

        return $expr;
    }

    private function factor(): Expr
    {
        $expr = $this->unary();
        while($this->match(TokenType::STAR, TokenType::SLASH, TokenType::MODULO)) {
            $operator = $this->previous();
            $right = $this->unary();
            $expr = new BinaryExpr($expr, $operator, $right);
        }

        return $expr;
    }

    private function unary(): Expr
    {
        if ($this->match(TokenType::BANG, TokenType::MINUS)) {
            $operator = $this->previous();
            $expr = $this->unary();
            return new UnaryExpr($operator, $expr);
        }

        return $this->primary();
    }

    private function primary(): Expr
    {
        if ($this->match(TokenType::FALSE)) return new LiteralExpr(false);
        if ($this->match(TokenType::TRUE)) return new LiteralExpr(true);
        if ($this->match(TokenType::NULL)) return new LiteralExpr(null);

        if ($this->match(TokenType::NUMBER, TokenType::STRING)) {
            return new LiteralExpr($this->previous()->literal);
        }

        if ($this->match(TokenType::LEFT_PAREN)) {
            $expr = $this->expression();

            $this->validate(TokenType::RIGHT_PAREN, "É preciso ter um  ')' após a expressão.");
            return new GroupingExpr($expr);
        }

        throw $this->error($this->peek(), "Espera uma expressão.");
    }

    private function validate(TokenType $kind, string $errorMessage): Token
    {
        if ($this->check($kind)) return $this->advance();
        throw $this->error($this->peek(), $errorMessage);
    }

    private function match(TokenType ...$kinds): bool
    {
        foreach($kinds as $kind) {
            if ($this->check($kind)) {
                $this->current++;
                return true;
            }
        }

        return false;
    }

    private function sincronize(): void
    {
        $this->advance();
        while(!$this->isAtEnd()) {
            if ($this->previous()->kind == TokenType::SEMICOLON) return;

            switch($this->peek()->kind) {
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

    private function check(TokenType $kind): bool
    {
        if ($this->isAtEnd()) return false;
        return $this->peek()->kind === $kind;
    }

    private function error(Token $token, string $message): ParserError
    {
        $this->errorHelper->error($token, $message);
        return new ParserError();
    }

    private function peek(): Token
    {
        return $this->tokens[$this->current];
    }

    private function advance(): Token
    {
        if (!$this->isAtEnd()) $this->current++;
        return $this->previous();
    }

    private function previous(): Token
    {
        return $this->tokens[$this->current - 1];
    }

    private function isAtEnd(): bool
    {
        return $this->peek()->kind === TokenType::EOF;
    }
}
