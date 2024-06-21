<?php

namespace Tests\Scanner;

use PHPUnit\Framework\TestCase;
use Phortugol\Enums\TokenType;
use Phortugol\Helpers\ErrorHelper;
use Phortugol\Scanner\Scanner;

class ScannerTest extends TestCase
{
    public function test_parse_string_with_error(): void
    {
        $error = new ErrorHelper();
        $source = "
        var a = 'Hello World;
        ";

        $scanner = new Scanner($source, $error);
        $scanner->scanTokens();
        $this->assertTrue($error->hadError);

        $source = "var b = \"Goodbye World;'";
        $scanner = new Scanner($source, $error);
        $scanner->scanTokens();
        $this->assertTrue($error->hadError);
    }

    /**
     * @dataProvider possibleStatements
     * @param string[] $expectedLexemes
     * @param TokenType[] $expectedKinds
     */
    public function test_scan_token_successfully(string $source, int $expectedCount, array $expectedLexemes, array $expectedKinds): void
    {
        $error = new ErrorHelper();
        $scanner = new Scanner($source, $error);
        $tokens = $scanner->scanTokens();
        $this->assertFalse($error->hadError);

        $this->assertCount($expectedCount, $tokens);
        $this->assertEquals(
            $expectedLexemes,
            array_map(fn($token) => $token->lexeme, $tokens)
        );

        foreach ($tokens as $index => $token) {
            $this->assertEquals($expectedKinds[$index], $token->kind);
        }
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function possibleStatements(): array
    {
        return [
            "should parse simple statement" => [
                "var a = 1 + 2;",
                8,
                ["var", "a", "=", "1", "+", "2", ";", ""],
                [
                    TokenType::VAR, TokenType::IDENTIFIER,
                    TokenType::EQUAL, TokenType::NUMBER,
                    TokenType::PLUS, TokenType::NUMBER,
                    TokenType::SEMICOLON, TokenType::EOF
                ]
            ],

            "should parse simple statement with both string types" => [
                "var a = 'Hello World'; var b = \"Goodbye World\";",
                11,
                ["var", "a", "=", "'Hello World'", ";", "var", "b", "=", "\"Goodbye World\"", ";", ""],
                [
                    TokenType::VAR, TokenType::IDENTIFIER,
                    TokenType::EQUAL, TokenType::STRING,
                    TokenType::SEMICOLON, TokenType::VAR, TokenType::IDENTIFIER,
                    TokenType::EQUAL, TokenType::STRING,
                    TokenType::SEMICOLON, TokenType::EOF
                ]
            ],

            "should parse simple statement with funcao" => [
                "funcao add(a, b) {
                retorne a + b;
                }
                ",
                15,
                ["funcao", "add", "(", "a", ",", "b", ")", "{", "retorne", "a", "+", "b", ";", "}", ""],
                [
                    TokenType::FUNCTION, TokenType::IDENTIFIER,
                    TokenType::LEFT_PAREN, TokenType::IDENTIFIER,
                    TokenType::COMMA, TokenType::IDENTIFIER,
                    TokenType::RIGHT_PAREN, TokenType::LEFT_BRACE,
                    TokenType::RETURN, TokenType::IDENTIFIER,
                    TokenType::PLUS, TokenType::IDENTIFIER,
                    TokenType::SEMICOLON, TokenType::RIGHT_BRACE,
                    TokenType::EOF
                ]
            ],

            "should parse simple statement with comments" => [
                "
                // Comments should be ignored
                // empty spaces too

                var a = (1 + 2);
                ",
                10,
                ["var", "a", "=", "(", "1", "+", "2", ")", ";", ""],
                [
                    TokenType::VAR, TokenType::IDENTIFIER,
                    TokenType::EQUAL, TokenType::LEFT_PAREN, TokenType::NUMBER,
                    TokenType::PLUS, TokenType::NUMBER, TokenType::RIGHT_PAREN,
                    TokenType::SEMICOLON, TokenType::EOF
                ]
            ],

            "should parse complex math operations" => [
                "
                var a = 1.5 % 2 * 3 / (4 - 5);
                ",
                16,
                ["var", "a", "=", "1.5", "%", "2", "*", "3", "/", "(", "4", "-", "5", ")", ";", ""],
                [
                    TokenType::VAR, TokenType::IDENTIFIER,
                    TokenType::EQUAL, TokenType::NUMBER,
                    TokenType::MODULO, TokenType::NUMBER,
                    TokenType::STAR, TokenType::NUMBER,
                    TokenType::SLASH, TokenType::LEFT_PAREN, TokenType::NUMBER,
                    TokenType::MINUS, TokenType::NUMBER, TokenType::RIGHT_PAREN,
                    TokenType::SEMICOLON, TokenType::EOF
                ]
            ],

            "should parse ++ and -- operations" => [
                "
                var a = 1;
                a++;
                a--;
                ",
                12,
                ["var", "a", "=", "1", ";", "a", "++", ";", "a", "--", ";", ""],
                [
                    TokenType::VAR, TokenType::IDENTIFIER,
                    TokenType::EQUAL, TokenType::NUMBER,
                    TokenType::SEMICOLON,

                    TokenType::IDENTIFIER, TokenType::PLUS_PLUS, TokenType::SEMICOLON,
                    TokenType::IDENTIFIER, TokenType::MINUS_MINUS, TokenType::SEMICOLON,
                    TokenType::EOF
                ]
            ],

            "should parse boolean operations" => [
                "
                var a = verdadeiro E falso OU verdadeiro;
                se (a) {
                retorne a;
                }
                ",
                19,
                ["var", "a", "=", "verdadeiro", "E", "falso", "OU", "verdadeiro", ";", "se", "(", "a", ")", "{", "retorne", "a", ";", "}", ""],
                [
                    TokenType::VAR, TokenType::IDENTIFIER,
                    TokenType::EQUAL, TokenType::TRUE,
                    TokenType::AND, TokenType::FALSE,
                    TokenType::OR, TokenType::TRUE,
                    TokenType::SEMICOLON, TokenType::IF, TokenType::LEFT_PAREN, TokenType::IDENTIFIER,
                    TokenType::RIGHT_PAREN, TokenType::LEFT_BRACE,
                    TokenType::RETURN, TokenType::IDENTIFIER,
                    TokenType::SEMICOLON, TokenType::RIGHT_BRACE,
                    TokenType::EOF
                ]
            ],
        ];
    }
}
