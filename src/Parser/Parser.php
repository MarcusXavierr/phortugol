<?php

namespace Phortugol\Parser;

use Phortugol\Enums\TokenType;
use Phortugol\Exceptions\ParserError;
use Phortugol\Expr\AssignExpr;
use Phortugol\Expr\BinaryExpr;
use Phortugol\Expr\ConditionalExpr;
use Phortugol\Expr\Expr;
use Phortugol\Expr\GroupingExpr;
use Phortugol\Expr\LiteralExpr;
use Phortugol\Expr\LogicalExpr;
use Phortugol\Expr\UnaryExpr;
use Phortugol\Expr\VarExpr;
use Phortugol\Helpers\ErrorHelper;
use Phortugol\Stmt\BlockStmt;
use Phortugol\Stmt\BreakStmt;
use Phortugol\Stmt\ContinueStmt;
use Phortugol\Stmt\ExpressionStmt;
use Phortugol\Stmt\IfStmt;
use Phortugol\Stmt\PrintStmt;
use Phortugol\Stmt\Stmt;
use Phortugol\Stmt\VarStmt;
use Phortugol\Stmt\WhileStmt;
use Phortugol\Token;

class Parser
{
    private ErrorHelper $errorHelper;
    /** @var Token[] $tokens */
    private array $tokens;
    private int $current = 0;
    private int $loopDepth = 0;

    /**
     * @param Token[] $tokens
     */
    public function __construct(ErrorHelper $error, array $tokens) {
        $this->errorHelper = $error;
        $this->tokens = $tokens;
    }

    /**
    * @return Stmt|null[]|null
    */
    public function parse(): array | null
    {
        $declarations = [];
        try {
            while(!$this->isAtEnd()) {
                array_push($declarations, $this->declaration());
            }
            return $declarations;
        } catch (ParserError $e) {
            return null;
        }
    }

    private function declaration(): ?Stmt
    {
        try {
            if ($this->match(TokenType::VAR)) return $this->varDeclaration();

            return $this->statement();
        } catch (ParserError $e) {
            $this->sincronize();
            return null;
        }
    }

    private function statement(): Stmt
    {
        if ($this->match(TokenType::PRINT)) return $this->printStmt();
        if ($this->match(TokenType::IF)) return $this->ifStmt();
        if ($this->match(TokenType::LEFT_BRACE)) return new BlockStmt($this->blockStatement());
        if ($this->match(TokenType::WHILE)) return $this->whileStmt();
        if ($this->match(TokenType::FOR)) return $this->forStmt();
        if ($this->match(TokenType::BREAK)) return $this->breakStmt();
        if ($this->match(TokenType::CONTINUE)) return $this->continueStmt();

        return $this->expressionStmt();
    }

    /**
     * @return Stmt[]
     */
    private function blockStatement(): array
    {
        $declarations = [];
        while(!$this->check(TokenType::RIGHT_BRACE) && !$this->isAtEnd()) {
            $declarations[] = $this->declaration();
        }

        $this->validate(TokenType::RIGHT_BRACE, "Esperado '}' no fim de um bloco");
        return $declarations;
    }

    private function varDeclaration(): Stmt
    {
        $identifier = $this->validate(TokenType::IDENTIFIER, "Necessário dar um nome para a sua variável");
        $initializer = null;
        if ($this->match(TokenType::EQUAL)) {
            $initializer = $this->expression();
        }

        $this->validate(TokenType::SEMICOLON, "Esperado um ';' após declarar uma variavel");
        return new VarStmt($identifier->lexeme, $initializer);
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

    private function ifStmt(): Stmt
    {
        $this->validate(TokenType::LEFT_PAREN, "É esperado um '(' logo após o 'se'.");
        $condition = $this->expression();
        $this->validate(TokenType::RIGHT_PAREN, "É esperado um ')' após uma expressão de 'se'.");

        $thenBranch = $this->statement();
        $elseBranch = null;
        if ($this->match(TokenType::ELSE)) {
            $elseBranch = $this->statement();
        }

        return new IfStmt($condition, $thenBranch, $elseBranch);
    }

    private function whileStmt(): Stmt
    {
        $this->validate(TokenType::LEFT_PAREN, "É esperado um '(' logo após o 'enquanto'.");
        $condition = $this->expression();
        $this->validate(TokenType::RIGHT_PAREN, "É esperado um ')' após uma expressão de 'enquanto'.");

        try {
            $this->loopDepth++;
            $body = $this->statement();
            return new WhileStmt($condition, $body);
        } finally {
            $this->loopDepth--;
        }
    }

    private function forStmt(): Stmt
    {
        $this->validate(TokenType::LEFT_PAREN, "É esperado um '(' logo após o 'enquanto'.");
        // Initializer
        $initializer = null;
        if ($this->match(TokenType::SEMICOLON)) {
            $initializer = null;
        }
        else if ($this->match(TokenType::VAR)) {
            $initializer = $this->varDeclaration();
        } else {
            $initializer = $this->expressionStmt();
        }

        // condition
        $condition = null;
        if (!$this->check(TokenType::SEMICOLON)) {
            $condition = $this->expression();
        }
        $this->validate(TokenType::SEMICOLON, "É esperado um ';' após a condição do 'enquanto'");

        // increment
        $increment = null;
        if (!$this->check(TokenType::RIGHT_PAREN)) {
            $increment = $this->expression();
        }
        $this->validate(TokenType::RIGHT_PAREN, "É esperado um ')' após uma expressão de 'enquanto'.");

        try {
            $this->loopDepth++;
            // Parse the body
            $body = $this->statement();

            // Now, mount the while loop with these parsed pieces, working backwards on the for
            if ($increment) {
                $body = new BlockStmt([
                    $body,
                    new ExpressionStmt($increment)
                ]);
            }

            if (!$condition) $condition = new LiteralExpr(true);

            $fallbackIncrement = $increment ? new ExpressionStmt($increment) : null;
            $body = new WhileStmt($condition, $body, $fallbackIncrement);

            if ($initializer) {
                $body = new BlockStmt([$initializer, $body]);
            }

            return $body;
        } finally {
            $this->loopDepth--;
        }
    }

    private function breakStmt(): Stmt
    {
        if ($this->loopDepth > 0) {
        $this->validate(TokenType::SEMICOLON, "É esperado um ';' no fim da expressão");
            return new BreakStmt();
        }

        throw $this->error($this->previous(), "'pare' só é permitido dentro de um laço");
    }

    private function continueStmt(): Stmt
    {
        if ($this->loopDepth > 0) {
        $this->validate(TokenType::SEMICOLON, "É esperado um ';' no fim da expressão");
            return new ContinueStmt();
        }

        throw $this->error($this->previous(), "'continue' só é permitido dentro de um laço");
    }

    // EXPRESSIONS
    private function expression(): Expr
    {
        return $this->assignment();
    }

    private function assignment(): Expr
    {
        $expr = $this->conditional();
        if ($this->match(TokenType::EQUAL)) {
            $equals = $this->previous();
            $assignment = $this->assignment();

            if ($expr instanceof VarExpr) {
                return new AssignExpr($expr->name, $assignment);
            }

            $this->error($equals, "Esperado uma variável antes do '='");
        }

        return $expr;
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

            $expr = new LogicalExpr($expr, $operator, $right);
        }

        return $expr;
    }

    private function logic_and(): Expr
    {
        $expr = $this->equality();

        while($this->match(TokenType::AND)) {
            $operator = $this->previous();
            $right = $this->equality();

            $expr = new LogicalExpr($expr, $operator, $right);
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

        if ($this->match(TokenType::IDENTIFIER)) {
            return new VarExpr($this->previous());
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
        $sizeTokens = count($this->tokens);
        if ($this->current >= $sizeTokens) {
            $lastLine = $this->tokens[$sizeTokens - 1]->line;
            return new Token(TokenType::EOF, null, "", $lastLine);
        }

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
