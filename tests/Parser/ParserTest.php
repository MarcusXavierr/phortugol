<?php

namespace Tests\Parser;

use PHPUnit\Framework\TestCase;
use Phortugol\Enums\TokenType;
use Phortugol\Expr\BinaryExpr;
use Phortugol\Expr\Expr;
use Phortugol\Expr\GroupingExpr;
use Phortugol\Expr\LiteralExpr;
use Phortugol\Expr\UnaryExpr;
use Phortugol\Expr\ConditionalExpr;
use Phortugol\Parser\Parser;
use Phortugol\Helpers\ErrorHelper;
use Phortugol\Stmt\ExpressionStmt;
use Phortugol\Token;

class ParserTest extends TestCase
{
    protected ErrorHelper $errorHelper;

    public function setUp(): void
    {
        parent::setUp();
        ob_start();
        $this->errorHelper = new ErrorHelper();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        ob_end_clean();
    }

    public function test_parse_with_error(): void
    {
        $tokens = [
            token(TokenType::NUMBER, 1),
            token(TokenType::PLUS),
            token(TokenType::PLUS),
        ];

        $parser = new Parser($this->errorHelper, $tokens);
        $parser->parse();
        $this->assertTrue($this->errorHelper->hadError);
    }

    /**
     * @dataProvider possible_expressions
     * @param Token[] $tokens
     */
    public function test_parse_without_error(array $tokens, Expr $expected): void
    {
        $parser = new Parser($this->errorHelper, $tokens);
        $expr = $parser->parse();
        $this->assertFalse($this->errorHelper->hadError);
        $this->assertEquals($expected, $expr);
    }

    /**
     * @return array<string, array{tokens: Token[], expected: Expr}>
     */
    public static function possible_expressions(): array
    {
        return [
            "should parse a simple expression" => [
                "tokens" => [
                    token(TokenType::NUMBER, 1),
                    token(TokenType::PLUS),
                    token(TokenType::NUMBER, 2),
                    token(TokenType::EOF),
                ],
                "expected" => new BinaryExpr(
                    new LiteralExpr(1),
                    new Token(TokenType::PLUS, null, "+", 1),
                    new LiteralExpr(2)
                )
            ],
            "should parse a grouping expression" => [
                "tokens" => [
                    token(TokenType::NUMBER, 2),   // 2
                    token(TokenType::STAR),        // *
                    token(TokenType::MINUS),       // -
                    token(TokenType::LEFT_PAREN),  // (
                    token(TokenType::NUMBER, 3),   // 3
                    token(TokenType::RIGHT_PAREN), // )
                    token(TokenType::EOF),
                ],
                "expected" => new BinaryExpr(
                    new LiteralExpr(2),
                    token(TokenType::STAR),
                    new UnaryExpr(
                        token(TokenType::MINUS),
                        new GroupingExpr(
                            new LiteralExpr(3)
                        )
                    )
                )
            ],
            "should parse a ternary expression" => [
                "tokens" => [
                    token(TokenType::TRUE),
                    token(TokenType::QUESTION),
                    token(TokenType::NUMBER, 1),
                    token(TokenType::COLON),
                    token(TokenType::NUMBER, 2),
                    token(TokenType::EOF),
                ],
                "expected" => new ConditionalExpr(
                    new LiteralExpr(true),
                    new LiteralExpr(1),
                    new LiteralExpr(2)
                )
            ]
        ];
    }
}

function token(TokenType $kind, mixed $literal = null): Token
{
    $lexeme = $literal ?? $kind->value;
    return new Token($kind, $literal, $lexeme, 1);
}
