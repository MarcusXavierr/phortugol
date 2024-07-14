<?php

namespace Tests\Parser;

use PHPUnit\Framework\TestCase;
use Phortugol\Enums\TokenType;
use Phortugol\Expr\AssignExpr;
use Phortugol\Expr\BinaryExpr;
use Phortugol\Expr\Expr;
use Phortugol\Expr\GroupingExpr;
use Phortugol\Expr\LiteralExpr;
use Phortugol\Expr\LogicalExpr;
use Phortugol\Expr\UnaryExpr;
use Phortugol\Expr\ConditionalExpr;
use Phortugol\Expr\VarExpr;
use Phortugol\Parser\Parser;
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
                "expected" => new LogicalExpr(
                    literal(true),
                    token(TokenType::OR),
                    new LogicalExpr(
                        literal(false),
                        token(TokenType::AND),
                        literal(true)
                    )
                )
            ],
            "should parse var usage on expression" => [
                "tokens" => [
                    numToken(1),
                    token("+"),
                    token(TokenType::IDENTIFIER, 'a'),
                    token(";")
                ],
                "expected" => new BinaryExpr(
                    literal(1),
                    token(TokenType::PLUS),
                    new VarExpr(token(TokenType::IDENTIFIER, 'a'))
                )
            ],
            "should parse var assignment" => [
                "tokens" => [
                    token(TokenType::IDENTIFIER, 'a'),
                    token(TokenType::EQUAL),
                    numToken(10),
                    token(";")
                ],
                "expected" => new AssignExpr(
                    token(TokenType::IDENTIFIER, 'a'),
                    literal(10),
                )
            ],
        ];
    }

    public function test_throw_error_on_break_outside_loop(): void
    {
        $tokens = [
            token(TokenType::BREAK),
            token(TokenType::SEMICOLON),
        ];

        $parser = new Parser($this->errorHelper, $tokens);
        $parser->parse();
        $this->assertTrue($this->errorHelper->hadError);
    }

    /**
     * @dataProvider possible_statements
     * @param Token[] $tokens
     */
    public function test_parse_statements(array $tokens, Stmt $expected): void
    {
        array_push($tokens, token(TokenType::EOF));

        $parser = new Parser($this->errorHelper, $tokens);
        $result = $parser->parse();

        $this->assertFalse($this->errorHelper->hadError);
        $this->assertEquals($expected, $result[0]);
    }

    /**
     * @return array<string, array{tokens: Token[], expected: Expr}>
     */
    public static function possible_statements(): array
    {
        return [
            "should parse a hello world" => [
                "tokens" => [
                    token('escreva'),
                    token(TokenType::STRING, 'oi mundo'),
                    token(';')
                ],
                "expected" => new PrintStmt(
                    new LiteralExpr('oi mundo')
                )
            ],
            "should parse a var declaration with initializer" => [
                "tokens" => [
                    token('var'),
                    token(TokenType::IDENTIFIER, 'a'),
                    token('='),
                    token(TokenType::NUMBER, 1),
                    token(';')
                ],
                "expected" => new VarStmt(
                    'a',
                    literal(1)
                )
            ],
            "should parse a var declaration without initializer" => [
                "tokens" => [
                    token('var'),
                    token(TokenType::IDENTIFIER, 'a'),
                    token(';')
                ],
                "expected" => new VarStmt(
                    'a',
                    null
                )
            ],
            "should parse a block" => [
                "tokens" => [
                    token('{'),
                    token('escreva'),
                    token(TokenType::STRING, 'oi'),
                    token(';'),
                    token('}')
                ],
                "expected" => new BlockStmt([
                    new PrintStmt(literal('oi'))
                ])
            ],
            "should parse an if statement" => [
                "tokens" => [
                    token('se'),
                    token('('),
                    token(TokenType::TRUE),
                    token(')'),
                    token('{'),
                    token('}'),
                ],
                "expected" => new IfStmt(
                    new LiteralExpr(true),
                    new BlockStmt([]),
                    null
                )
            ],
            "should parse if and else" => [
                "tokens" => [
                    token('se'),
                    token('('),
                    token(TokenType::TRUE),
                    token(')'),
                    token('{'),
                    token('escreva'),
                    token(TokenType::STRING, 'oi mundo'),
                    token(';'),
                    token('}'),
                    token('senao'),
                    numToken(10),
                    token('+'),
                    numToken(20),
                    token(';')
                ],
                "expected" => new IfStmt(
                    new LiteralExpr(true),
                    new BlockStmt([
                        new PrintStmt(literal('oi mundo'))
                    ]),
                    new ExpressionStmt(
                        new BinaryExpr(
                            new LiteralExpr(10),
                            token(TokenType::PLUS),
                            new LiteralExpr(20)
                        )
                    )
                )
            ],
            "should parse a while statement with body" => [
                "tokens" => [
                    token('enquanto'),
                    token('('),
                    token(TokenType::TRUE),
                    token(')'),
                    token('{'),
                    token('escreva'),
                    token(TokenType::STRING, 'oi mundo'),
                    token(';'),
                    token('}'),
                ],
                "expected" => new WhileStmt(
                    new LiteralExpr(true),
                    new BlockStmt([
                        new PrintStmt(literal('oi mundo'))
                    ])
                )
            ],
            "should parse a for statement without initializer and increment" => [
                "tokens" => [
                    token('repita'),
                    token('('),
                    token(';'),
                    token('verdadeiro'),
                    token(';'),
                    token(')'),
                    token('{'),
                    token('escreva'),
                    token(TokenType::STRING, 'teste'),
                    token(';'),
                    token('}'),
                ],
                "expected" => new WhileStmt(
                    new LiteralExpr(true),
                    new BlockStmt([
                        new PrintStmt(literal('teste'))
                    ])
                )
            ],
            "should parse a for statement with initializer and increment" => [
                "tokens" => [
                    token('repita'),
                    token('('),
                    token('var'),
                    token(TokenType::IDENTIFIER, 'i'),
                    token('='),
                    numToken(0),
                    token(';'),
                    token(TokenType::IDENTIFIER, 'i'),
                    token('<'),
                    numToken(10),
                    token(';'),
                    token(TokenType::IDENTIFIER, 'i'),
                    token('='),
                    token(TokenType::IDENTIFIER, 'i'),
                    token('+'),
                    numToken(1),
                    token(')'),
                    token('{'),
                    token('escreva'),
                    token(TokenType::STRING, 'teste'),
                    token(';'),
                    token('}'),
                ],
                "expected" => new BlockStmt([
                    new VarStmt('i', literal(0)),
                    new WhileStmt(
                        new BinaryExpr(
                            new VarExpr(token(TokenType::IDENTIFIER, 'i')),
                            token(TokenType::LESS),
                            literal(10)
                        ),
                        new BlockStmt([
                            new BlockStmt([
                                new PrintStmt(literal('teste')),
                            ]),
                            new ExpressionStmt(
                                new AssignExpr(
                                    token(TokenType::IDENTIFIER, 'i'),
                                    new BinaryExpr(
                                        new VarExpr(token(TokenType::IDENTIFIER, 'i')),
                                        token(TokenType::PLUS),
                                        literal(1)
                                    )
                                )
                            )
                        ]),
                        new ExpressionStmt(
                            new AssignExpr(
                                token(TokenType::IDENTIFIER, 'i'),
                                new BinaryExpr(
                                    new VarExpr(token(TokenType::IDENTIFIER, 'i')),
                                    token(TokenType::PLUS),
                                    literal(1)
                                )
                            )
                        )
                    )
                ])
            ],
            "should parse break statement" => [
                "tokens" => [
                    token('enquanto'),
                    token('('),
                    token(TokenType::TRUE),
                    token(')'),
                    token('{'),
                    token('pare'),
                    token(';'),
                    token('}'),
                ],
                "expected" => new WhileStmt(
                    literal(true),
                    new BlockStmt([
                        new BreakStmt()
                    ])
                )
            ],
            "should parse continue statement" => [
                "tokens" => [
                    token('enquanto'),
                    token('('),
                    token(TokenType::TRUE),
                    token(')'),
                    token('{'),
                    token('continue'),
                    token(';'),
                    token('}'),
                ],
                "expected" => new WhileStmt(
                    literal(true),
                    new BlockStmt([
                        new ContinueStmt()
                    ])
                )
            ],
            "should parse ++ and -- statements as variable assignment with increment or decrement by 1" => [
                "tokens" => [
                    token("{"),
                    token(TokenType::IDENTIFIER, 'a'),
                    token("++"),
                    token(';'),
                    token(TokenType::IDENTIFIER, 'a'),
                    token("--"),
                    token(';'),
                    token('}')
                ],
                "expected" => new BlockStmt([
                    new ExpressionStmt(
                        new AssignExpr(
                            token(TokenType::IDENTIFIER, 'a'),
                            new BinaryExpr(
                                new VarExpr(token(TokenType::IDENTIFIER, 'a')),
                                token(TokenType::PLUS),
                                literal(1)
                            )
                        )
                    ),
                    new ExpressionStmt(
                        new AssignExpr(
                            token(TokenType::IDENTIFIER, 'a'),
                            new BinaryExpr(
                                new VarExpr(token(TokenType::IDENTIFIER, 'a')),
                                token(TokenType::MINUS),
                                literal(1)
                            )
                        )
                    ),
                ])
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
