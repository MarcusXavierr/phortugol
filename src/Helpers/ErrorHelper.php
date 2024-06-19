<?php

namespace Toyjs\Toyjs\Helpers;

use Toyjs\Toyjs\Enums\TokenType;
use Toyjs\Toyjs\Token;


// TODO: Inject a buffer on constructor to mock echo
class ErrorHelper {
    public bool $hadError = false;

    public function report(int $line, string $message): void
    {
        echo 'Error at line ' . $line . ': ' . $message . PHP_EOL;
        $this->hadError = true;
    }

    public function error(Token $token, string $message): void
    {
        if ($token->kind == TokenType::EOF) {
            $this->report($token->line, " at end " . $message);
        } else {
            $this->report($token->line, "at '" . $token->lexeme . "'  " . $message);
        }
    }
}
