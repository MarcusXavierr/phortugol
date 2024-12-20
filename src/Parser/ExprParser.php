<?php

namespace Phortugol\Parser;

use Phortugol\Enums\TokenType;
use Phortugol\Expr\ArrayDefExpr;
use Phortugol\Expr\ArrayGetExpr;
use Phortugol\Expr\ArraySetExpr;
use Phortugol\Expr\CallExpr;
use Phortugol\Expr\GetExpr;
use Phortugol\Expr\LambdaExpr;
use Phortugol\Expr\LiteralExpr;
use Phortugol\Expr\AssignExpr;
use Phortugol\Expr\BinaryExpr;
use Phortugol\Expr\ConditionalExpr;
use Phortugol\Expr\Expr;
use Phortugol\Expr\GroupingExpr;
use Phortugol\Expr\LogicalExpr;
use Phortugol\Expr\SetExpr;
use Phortugol\Expr\ThisExpr;
use Phortugol\Expr\UnaryExpr;
use Phortugol\Expr\VarExpr;
use Phortugol\Stmt\ReturnStmt;
use Phortugol\Token;

class ExprParser
{
    private ParserHelper $helper;
    private Parser $parser;

    /**
     * @param Token[] $tokens
     */
    public function __construct(ParserHelper $helper, Parser $parser)
    {
        $this->helper = $helper;
        $this->parser = $parser;
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

        return $this->lambda();
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

    private function lambda(): Expr
    {
        // TODO: Maybe send this token to the Expr, so you can use for better error messages
        if (!$this->helper->check(TokenType::LEFT_PAREN) || !$this->helper->isLambda()) {
            return $this->assignment();
        }

        $this->helper->validate(TokenType::LEFT_PAREN, "É esperado um '(' no início de uma função lambda.");

        $parameters = [];
        if (!$this->helper->check(TokenType::RIGHT_PAREN)) {
            do {
                if (count($parameters) > 255) {
                    $this->helper->error($this->helper->peek(), "Não é possível ter mais de 255 parâmetros.");
                }

                array_push($parameters, $this->helper->validate(TokenType::IDENTIFIER, "Experado o nome de um parâmetro."));
            } while ($this->helper->match(TokenType::COMMA));
        }

        $this->helper->validate(TokenType::RIGHT_PAREN, "É esperado um ')' depois dos parâmetros.");
        $token = $this->helper->validate(TokenType::LAMBDA_RETURN, "É esperado o operador '=>' depois dos parâmetros");

        $body = [];
        if ($this->helper->match(TokenType::LEFT_BRACE)) {
            $body = $this->parser->blockStatement();
        } else {
            array_push($body, new ReturnStmt($token, $this->expression()));
        }

        return new LambdaExpr($parameters, $body);
    }

    private function assignment(): Expr
    {
        $expr = $this->conditional();
        if ($this->helper->match(TokenType::EQUAL)) {
            $equals = $this->helper->previous();
            $assignment = $this->assignment();

            if ($expr instanceof VarExpr) {
                return new AssignExpr($expr->name, $assignment);
            } else if ($expr instanceof GetExpr) {
                return new SetExpr($expr->object, $expr->name, $assignment);
            } else if ($expr instanceof ArrayGetExpr) {
                return new ArraySetExpr($expr->bracket, $expr->array, $expr->index, $assignment);
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

        return $this->call();
    }

    private function call(): Expr
    {
        $expr = $this->arrayDef();

        while (true) {
            if ($this->helper->match(TokenType::LEFT_PAREN)) {
                $expr = $this->finishCall($expr);
            } else if ($this->helper->match(TokenType::DOT)) { // Parse object attribute call
                $name = $this->helper->validate(TokenType::IDENTIFIER, "É esperado o nome da propriedade após acessar um objeto com '.'");
                $expr = new GetExpr($expr, $name);
            } else if ($this->helper->match(TokenType::LEFT_BRACKET)) { // Parse array get
                $token = $this->helper->previous();
                $index = $this->expression();
                $this->helper->validate(TokenType::RIGHT_BRACKET, "É esperado um '[' no fim do array");
                return new ArrayGetExpr($token, $expr, $index);
            } else {
                break;
            }
        }

        return $expr;
    }

    private function finishCall(Expr $callee): Expr
    {
        $arguments = [];
        if (!$this->helper->check(TokenType::RIGHT_PAREN)) {
            do {
                if (count($arguments) > 255) {
                    $this->helper->error($this->helper->peek(), "Não é possível ter mais de 255 argumentos");
                }
                array_push($arguments, $this->expression());
            } while($this->helper->match(TokenType::COMMA));
        }

        $paren = $this->helper->validate(TokenType::RIGHT_PAREN, "Experado ')' depois da chamada de uma função");
        return new CallExpr($callee, $paren, $arguments);
    }

    private function arrayDef(): Expr
    {
        if (!$this->helper->check(TokenType::LEFT_BRACKET)) {
            return $this->primary();
        }

        $leftBracket = $this->helper->validate(TokenType::LEFT_BRACKET, "Esperado um '[' antes do início da lista.");
        $elements = [];
        if (!$this->helper->check(TokenType::RIGHT_BRACKET)) {
            do {
                array_push($elements, $this->expression());
            } while($this->helper->match(TokenType::COMMA));
        }

        $this->helper->validate(TokenType::RIGHT_BRACKET, "Esperado ']' após a criação de um array.");
        return new ArrayDefExpr($leftBracket, $elements);
    }

    private function primary(): Expr
    {
        if ($this->helper->match(TokenType::FALSE)) return new LiteralExpr(false);
        if ($this->helper->match(TokenType::TRUE)) return new LiteralExpr(true);
        if ($this->helper->match(TokenType::NULL)) return new LiteralExpr(null);
        if ($this->helper->match(TokenType::NL)) return new LiteralExpr("\n");
        if ($this->helper->match(TokenType::THIS)) return new ThisExpr($this->helper->previous());

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


        echo 'oi';
        throw $this->helper->error($this->helper->peek(), "Espera uma expressão.");
    }
}
