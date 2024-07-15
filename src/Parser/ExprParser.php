<?php

namespace Phortugol\Parser;

use Phortugol\Enums\TokenType;
use Phortugol\Expr\LiteralExpr;
use Phortugol\Expr\AssignExpr;
use Phortugol\Expr\BinaryExpr;
use Phortugol\Expr\ConditionalExpr;
use Phortugol\Expr\Expr;
use Phortugol\Expr\GroupingExpr;
use Phortugol\Expr\LogicalExpr;
use Phortugol\Expr\UnaryExpr;
use Phortugol\Expr\VarExpr;
use Phortugol\Token;

class ExprParser
{
    private ParserHelper $helper;

    /**
     * @param Token[] $tokens
     */
    public function __construct(ParserHelper $helper)
    {
        $this->helper = $helper;
    }

    // EXPRESSIONS
    public function expression(): Expr
    {
        // parsing ++ and -- for variables and desugaring to +=/-=
        if ($this->helper->check(TokenType::IDENTIFIER)) {
            if ($this->helper->peekNext()->kind == TokenType::PLUS_PLUS || $this->helper->peekNext()->kind == TokenType::MINUS_MINUS) {
                return $this->postfixVarIncrementDecrement();
            }
        }

        return $this->assignment();
    }

    private function postfixVarIncrementDecrement(): Expr
    {
        $identifier = $this->helper->advance();
        $token = $this->helper->advance();
        $this->helper->match(TokenType::SEMICOLON); // consume the semicolon (if it exists)
        $operator = $token->kind == TokenType::PLUS_PLUS ? TokenType::PLUS : TokenType::MINUS;
        $operationToken = new Token($operator, null, $operator->value, $token->line);

        return new AssignExpr(
            $identifier,
            new BinaryExpr(
                new VarExpr($identifier), $operationToken, new LiteralExpr(1)
            )
        );
    }

    private function assignment(): Expr
    {
        $expr = $this->conditional();
        if ($this->helper->match(TokenType::EQUAL)) {
            $equals = $this->helper->previous();
            $assignment = $this->assignment();

            if ($expr instanceof VarExpr) {
                return new AssignExpr($expr->name, $assignment);
            }

            $this->helper->error($equals, "Esperado uma variável antes do '='");
        }

        return $expr;
    }

    private function conditional(): Expr
    {
        $expr = $this->logic_or();

        if ($this->helper->match(TokenType::QUESTION)) {
            $left = $this->expression();

            if (!$this->helper->match(TokenType::COLON)) {
                throw $this->helper->error($this->helper->peek(), "É esperado um ':' e a expressão caso seja falso.");
            }
            $right = $this->conditional();
            $expr = new ConditionalExpr($expr, $left, $right);
        }

        return $expr;
    }

    private function logic_or(): Expr
    {
        $expr = $this->logic_and();

        while ($this->helper->match(TokenType::OR)) {
            $operator = $this->helper->previous();
            $right = $this->logic_and();

            $expr = new LogicalExpr($expr, $operator, $right);
        }

        return $expr;
    }

    private function logic_and(): Expr
    {
        $expr = $this->equality();

        while ($this->helper->match(TokenType::AND)) {
            $operator = $this->helper->previous();
            $right = $this->equality();

            $expr = new LogicalExpr($expr, $operator, $right);
        }

        return $expr;
    }

    private function equality(): Expr
    {
        $expr = $this->comparison();
        while ($this->helper->match(TokenType::BANG_EQUAL, TokenType::EQUAL_EQUAL)) {
            $operator = $this->helper->previous();
            $right = $this->comparison();
            $expr = new BinaryExpr($expr, $operator, $right);
        }

        return $expr;
    }

    private function comparison(): Expr
    {
        $expr = $this->term();

        while ($this->helper->match(TokenType::GREATER, TokenType::GREATER_EQUAL, TokenType::LESS, TokenType::LESS_EQUAL)) {
            $operator = $this->helper->previous();
            $right = $this->term();
            $expr = new BinaryExpr($expr, $operator, $right);
        }

        return $expr;
    }

    private function term(): Expr
    {
        $expr = $this->factor();

        while ($this->helper->match(TokenType::MINUS, TokenType::PLUS)) {
            $operator = $this->helper->previous();
            $right = $this->factor();
            $expr = new BinaryExpr($expr, $operator, $right);
        }

        return $expr;
    }

    private function factor(): Expr
    {
        $expr = $this->unary();
        while ($this->helper->match(TokenType::STAR, TokenType::SLASH, TokenType::MODULO)) {
            $operator = $this->helper->previous();
            $right = $this->unary();
            $expr = new BinaryExpr($expr, $operator, $right);
        }

        return $expr;
    }

    private function unary(): Expr
    {
        if ($this->helper->match(TokenType::BANG, TokenType::MINUS)) {
            $operator = $this->helper->previous();
            $expr = $this->unary();
            return new UnaryExpr($operator, $expr);
        }

        return $this->primary();
    }

    private function primary(): Expr
    {
        if ($this->helper->match(TokenType::FALSE)) return new LiteralExpr(false);
        if ($this->helper->match(TokenType::TRUE)) return new LiteralExpr(true);
        if ($this->helper->match(TokenType::NULL)) return new LiteralExpr(null);

        if ($this->helper->match(TokenType::NUMBER, TokenType::STRING)) {
            return new LiteralExpr($this->helper->previous()->literal);
        }

        if ($this->helper->match(TokenType::IDENTIFIER)) {
            return new VarExpr($this->helper->previous());
        }

        if ($this->helper->match(TokenType::LEFT_PAREN)) {
            $expr = $this->expression();

            $this->helper->validate(TokenType::RIGHT_PAREN, "É preciso ter um  ')' após a expressão.");
            return new GroupingExpr($expr);
        }


        throw $this->helper->error($this->helper->peek(), "Espera uma expressão.");
    }
}
