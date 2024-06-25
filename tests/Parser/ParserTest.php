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
    public function test_parse_expression_without_error(array $tokens, Expr $expected): void
    {
        $expectedStmt = new ExpressionStmt($expected);
        array_push($tokens, token(TokenType::EOF));

        $parser = new Parser($this->errorHelper, $tokens);
        $result = $parser->parse();

        $this->assertFalse($this->errorHelper->hadError);
        $this->assertEquals($expectedStmt, $result[0]);
    }

    /**
     * @return array<string, array{tokens: Token[], expected: Expr}>
     */
    public static function possible_expressions(): array
    {
        return [
            "should parse a simple expression" => [
                "tokens" => [
                    numToken(1),
                    token("+"),
                    numToken(2),
                    token(";")
                ],
                "expected" => new BinaryExpr(
                    literal(1),
                    token(TokenType::PLUS),
                    literal(2)
                )
            ],
            "should parse a grouping expression" => [
                "tokens" => [
                    numToken(2),
                    token("*"),
                    token("-"),
                    token("("),
                    numToken(3),
                    token(")"),
                    token(";")
                ],
                "expected" => new BinaryExpr(
                    literal(2),
                    token("*"),
                    new UnaryExpr(
                        token("-"),
                        new GroupingExpr(
                            literal(3)
                        )
                    )
                )
            ],
            "should parse a ternary expression" => [
                "tokens" => [
                    token(TokenType::TRUE),
                    token("?"),
                    numToken(1),
                    token(":"),
                    numToken(2),
                    token(";")
                ],
                "expected" => new ConditionalExpr(
                    literal(true),
                    literal(1),
                    literal(2)
                )
            ],
            "sould parse boolean expressions" => [
                "tokens" => [
                    token(TokenType::TRUE),
                    token(TokenType::OR),
                    token(TokenType::FALSE),
                    token(TokenType::AND),
                    token(TokenType::TRUE),
                    token(";")
                ],
                "expected" => new BinaryExpr(
                    literal(true),
                    token(TokenType::OR),
                    new BinaryExpr(
                        literal(false),
                        token(TokenType::AND),
                        literal(true)
                    )
                )
            ],
        ];
    }
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
