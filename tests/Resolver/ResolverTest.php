<?php

namespace Tests\Resolver;

use PHPUnit\Framework\TestCase;
use Phortugol\Enums\TokenType;
use Phortugol\Expr\LiteralExpr;
use Phortugol\Expr\VarExpr;
use Phortugol\Helpers\ErrorHelper;
use Phortugol\Interpreter\Interpreter;
use Phortugol\Resolver\Resolver;
use Phortugol\Stmt\BlockStmt;
use Phortugol\Stmt\ExpressionStmt;
use Phortugol\Stmt\FunctionStmt;
use Phortugol\Stmt\IfStmt;
use Phortugol\Stmt\PrintStmt;
use Phortugol\Stmt\ReturnStmt;
use Phortugol\Stmt\VarStmt;
use Phortugol\Token;

class ResolverTest extends TestCase
{
    protected ErrorHelper $errorHelper;
    protected Interpreter $interpreter;

    protected function setUp(): void
    {
        parent::setUp();
        ob_start();
        $this->errorHelper = new ErrorHelper();
        $this->interpreter = new Interpreter($this->errorHelper);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        ob_end_clean();
    }

    public function testResolveScopedVariable(): void
    {
        $globalVariableUsage = newVar('a');
        $localVariableUsage = newVar('b');

        $program = [
            new VarStmt('a', literal(1)),
            new BlockStmt([
                new VarStmt('b', literal(2)),
                new ExpressionStmt($localVariableUsage)
            ]),
            new PrintStmt($globalVariableUsage)
        ];

        $resolver = new Resolver($this->interpreter, $this->errorHelper);
        $resolver->resolve($program);

        $this->assertFalse($this->errorHelper->hadError);
        $this->assertEquals(0, $this->interpreter->locals->get($localVariableUsage));
        $this->assertEquals(null, $this->interpreter->locals->get($globalVariableUsage, null));
    }

    public function testResolveNestedScopedVariable(): void
    {
        $scopedVariableUsage = newVar('a');
        $functionArgumentUsage = newVar('x');

        $program = [
            new BlockStmt([
                new VarStmt('a', literal(1)),
                new FunctionStmt(token(TokenType::IDENTIFIER, 'foo'), [token(TokenType::IDENTIFIER, 'x')], [
                    new IfStmt(
                        literal(true),
                        new BlockStmt([
                            new PrintStmt($scopedVariableUsage),
                            new ReturnStmt(token(TokenType::RETURN), $functionArgumentUsage)
                        ]),
                        new PrintStmt(newVar('a'))
                    )
                ])
            ])
        ];

        $resolver = new Resolver($this->interpreter, $this->errorHelper);
        $resolver->resolve($program);

        $this->assertFalse($this->errorHelper->hadError);
        $this->assertEquals(2, $this->interpreter->locals->get($scopedVariableUsage));
        $this->assertEquals(1, $this->interpreter->locals->get($functionArgumentUsage));
    }

    public function testValidateCannotCallReturnOutsideFunction(): void
    {
        $program = [
            new ReturnStmt(token('retorne'), literal(1))
        ];

        $resolver = new Resolver($this->interpreter, $this->errorHelper);
        $resolver->resolve($program);

        $this->assertTrue($this->errorHelper->hadError);
    }
}

function newVar(string $name): VarExpr
{
    return new VarExpr(token(TokenType::IDENTIFIER, $name));
}

function literal(mixed $value): LiteralExpr
{
    return new LiteralExpr($value);
}

function numToken(float|int $value): Token
{
    return token(TokenType::NUMBER, $value);
}

function token(string|TokenType $kind, mixed $literal = null): Token
{
    if (is_string($kind)) {
        $kind = TokenType::from($kind);
    }
    $lexeme = $literal ?? $kind->value;
    return new Token($kind, $literal, $lexeme, 1);
}
