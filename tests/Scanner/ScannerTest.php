<?php

namespace Tests\Scanner;

use PHPUnit\Framework\TestCase;
use Toyjs\Toyjs\Helpers\ErrorHelper;
use Toyjs\Toyjs\Scanner\Scanner;

class ScannerTest extends TestCase
{
    public function test_parse_string_with_error(): void
    {
        $error = new ErrorHelper();
        $source = "
        const a = 'Hello World;
        ";

        $scanner = new Scanner($source, $error);
        $scanner->scanTokens();
        $this->assertTrue($error->hadError);

        $source = "let b = \"Goodbye World;'";
        $scanner = new Scanner($source, $error);
        $scanner->scanTokens();
        $this->assertTrue($error->hadError);
    }

    /**
     * @dataProvider possibleStatements
     * @param string[] $expectedLexemes
     */
    public function test_scan_token_successfully(string $source, int $expectedCount, array $expectedLexemes): void
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
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function possibleStatements(): array
    {
        return [
            "should parse simple statement" => [
                "const a = 1 + 2;",
                8,
                ["const", "a", "=", "1", "+", "2", ";", ""]
            ],

            "should parse simple statement with both string types" => [
                "const a = 'Hello World'; let b = \"Goodbye World\";",
                11,
                ["const", "a", "=", "'Hello World'", ";", "let", "b", "=", "\"Goodbye World\"", ";", ""]
            ],

            "should parse simple statement with function" => [
                "function add(a, b) {
                return a + b;
                }
                ",
                15,
                ["function", "add", "(", "a", ",", "b", ")", "{", "return", "a", "+", "b", ";", "}", ""]
            ],

            "should parse simple statement with comments" => [
                "
                // Comments should be ignored
                // empty spaces too

                const a = (1 + 2);
                ",
                10,
                ["const", "a", "=", "(", "1", "+", "2", ")", ";", ""]
            ],

            "should parse complex math operations" => [
                "
                var a = 1.5 % 2 * 3 / (4 - 5);
                ",
                16,
                ["var", "a", "=", "1.5", "%", "2", "*", "3", "/", "(", "4", "-", "5", ")", ";", ""]
            ],

            "should parse ++ and -- operations" => [
                "
                var a = 1;
                a++;
                a--;
                ",
                12,
                ["var", "a", "=", "1", ";", "a", "++", ";", "a", "--", ";", ""]
            ],

            "should parse boolean operations" => [
                "
                var a = true && false || true;
                if (a) {
                return a;
                }
                ",
                19,
                ["var", "a", "=", "true", "&&", "false", "||", "true", ";", "if", "(", "a", ")", "{", "return", "a", ";", "}", ""]
            ],
        ];
    }
}
