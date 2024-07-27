<?php

namespace Phortugol\Parser;

use Phortugol\Enums\TokenType;
use Phortugol\Exceptions\ParserError;
use Phortugol\Expr\LiteralExpr;
use Phortugol\Helpers\ErrorHelper;
use Phortugol\Stmt\BlockStmt;
use Phortugol\Stmt\BreakStmt;
use Phortugol\Stmt\ContinueStmt;
use Phortugol\Stmt\ExpressionStmt;
use Phortugol\Stmt\FunctionStmt;
use Phortugol\Stmt\IfStmt;
use Phortugol\Stmt\PrintStmt;
use Phortugol\Stmt\ReturnStmt;
use Phortugol\Stmt\Stmt;
use Phortugol\Stmt\VarStmt;
use Phortugol\Stmt\WhileStmt;
use Phortugol\Token;

class Parser
{
    private ParserHelper $helper;
    private ExprParser $exprParser;
    private int $current = 0;
    private int $loopDepth = 0;

    /**
     * @param Token[] $tokens
     */
    public function __construct(ErrorHelper $error, array $tokens)
    {
        $this->helper = new ParserHelper($error, $tokens, $this->current);
        $this->exprParser = new ExprParser($this->helper);
    }

    /**
     * @return Stmt|null[]|null
     */
    public function parse(): array|null
    {
        $declarations = [];
        try {
            while (!$this->helper->isAtEnd()) {
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
            if ($this->helper->match(TokenType::VAR)) return $this->varDeclaration();

            return $this->statement();
        } catch (ParserError $e) {
            $this->helper->sincronize();
            return null;
        }
    }

    private function statement(): Stmt
    {
        if ($this->helper->match(TokenType::PRINT)) return $this->printStmt();
        if ($this->helper->match(TokenType::IF)) return $this->ifStmt();
        if ($this->helper->match(TokenType::LEFT_BRACE)) return new BlockStmt($this->blockStatement());
        if ($this->helper->match(TokenType::WHILE)) return $this->whileStmt();
        if ($this->helper->match(TokenType::FOR)) return $this->forStmt();
        if ($this->helper->match(TokenType::BREAK)) return $this->breakStmt();
        if ($this->helper->match(TokenType::CONTINUE)) return $this->continueStmt();
        if ($this->helper->match(TokenType::FUNCTION)) return $this->function('função');
        if ($this->helper->match(TokenType::RETURN)) return $this->returnStmt();

        return $this->expressionStmt();
    }

    /**
     * @return Stmt[]
     */
    private function blockStatement(): array
    {
        $declarations = [];
        while (!$this->helper->check(TokenType::RIGHT_BRACE) && !$this->helper->isAtEnd()) {
            $declarations[] = $this->declaration();
        }

        $this->helper->validate(TokenType::RIGHT_BRACE, "Esperado '}' no fim de um bloco");
        return $declarations;
    }

    private function varDeclaration(): Stmt
    {
        $identifier = $this->helper->validate(TokenType::IDENTIFIER, "Necessário dar um nome para a sua variável");
        $initializer = null;
        if ($this->helper->match(TokenType::EQUAL)) {
            $initializer = $this->exprParser->expression();
        }

        $this->helper->validate(TokenType::SEMICOLON, "Esperado um ';' após declarar uma variavel");
        return new VarStmt($identifier->lexeme, $initializer);
    }

    private function expressionStmt(): Stmt
    {
        $expr = $this->exprParser->expression();
        $this->helper->match(TokenType::SEMICOLON);
        // FIX: Não posso validar o ';' por causa da sintaxe do for de incremento. e nõa posso mover o parsing do ++ para uma expressão
        // $this->helper->validate(TokenType::SEMICOLON, "É esperado um ';' no fim da expressão");
        return new ExpressionStmt($expr);
    }

    // TODO: Parse print with parenthesis
    private function printStmt(): Stmt
    {
        $expr = $this->exprParser->expression();
        $this->helper->validate(TokenType::SEMICOLON, "É esperado um ';' no fim da expressão");
        return new PrintStmt($expr);
    }

    private function ifStmt(): Stmt
    {
        $this->helper->validate(TokenType::LEFT_PAREN, "É esperado um '(' logo após o 'se'.");
        $condition = $this->exprParser->expression();
        $this->helper->validate(TokenType::RIGHT_PAREN, "É esperado um ')' após uma expressão de 'se'.");

        $thenBranch = $this->statement();
        $elseBranch = null;
        if ($this->helper->match(TokenType::ELSE)) {
            $elseBranch = $this->statement();
        }

        return new IfStmt($condition, $thenBranch, $elseBranch);
    }

    private function whileStmt(): Stmt
    {
        $this->helper->validate(TokenType::LEFT_PAREN, "É esperado um '(' logo após o 'enquanto'.");
        $condition = $this->exprParser->expression();
        $this->helper->validate(TokenType::RIGHT_PAREN, "É esperado um ')' após uma expressão de 'enquanto'.");

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
        $this->helper->validate(TokenType::LEFT_PAREN, "É esperado um '(' logo após o 'enquanto'.");
        // Initializer
        $initializer = null;
        if ($this->helper->match(TokenType::SEMICOLON)) {
            $initializer = null;
        } else if ($this->helper->match(TokenType::VAR)) {
            $initializer = $this->varDeclaration();
        } else {
            $initializer = $this->expressionStmt();
        }

        // condition
        $condition = null;
        if (!$this->helper->check(TokenType::SEMICOLON)) {
            $condition = $this->exprParser->expression();
        }
        $this->helper->validate(TokenType::SEMICOLON, "É esperado um ';' após a condição do 'enquanto'");

        // increment
        $increment = null;
        if (!$this->helper->check(TokenType::RIGHT_PAREN)) {
            $increment = new ExpressionStmt($this->exprParser->expression());
        }
        $this->helper->validate(TokenType::RIGHT_PAREN, "É esperado um ')' após uma expressão de 'enquanto'.");

        try {
            $this->loopDepth++;
            // Parse the body
            $body = $this->statement();

            // Now, mount the while loop with these parsed pieces, working backwards on the for
            if ($increment) {
                $body = new BlockStmt([
                    $body,
                    $increment
                ]);
            }

            if (!$condition) $condition = new LiteralExpr(true);
            $body = new WhileStmt($condition, $body, $increment);

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
            $this->helper->validate(TokenType::SEMICOLON, "É esperado um ';' no fim da expressão");
            return new BreakStmt();
        }

        throw $this->helper->error($this->helper->previous(), "'pare' só é permitido dentro de um laço");
    }

    private function continueStmt(): Stmt
    {
        if ($this->loopDepth > 0) {
            $this->helper->validate(TokenType::SEMICOLON, "É esperado um ';' no fim da expressão");
            return new ContinueStmt();
        }

        throw $this->helper->error($this->helper->previous(), "'continue' só é permitido dentro de um laço.");
    }

    private function function(string $kind): Stmt
    {
        $name = $this->helper->validate(TokenType::IDENTIFIER, "É esperado um nome para a declaração de {$kind}.");

        $this->helper->validate(TokenType::LEFT_PAREN, "É esperado um '(' antes dos parâmetros.");
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
        $this->helper->validate(TokenType::LEFT_BRACE, "É esperado um '{'");

        $body = $this->blockStatement();
        return new FunctionStmt($name, $parameters, $body);
    }

    private function returnStmt(): Stmt
    {
        $keyword = $this->helper->previous();
        $expr = null;
        if (!$this->helper->check(TokenType::SEMICOLON)) {
            $expr = $this->exprParser->expression();
        }
        $this->helper->validate(TokenType::SEMICOLON, "É esperado um ';' no fim de um statement de retorno");

        return new ReturnStmt($keyword, $expr);
    }
}
