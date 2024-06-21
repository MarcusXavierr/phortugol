<?php

namespace Phortugol\Helpers;

use Phortugol\Enums\TokenType;
use Phortugol\Exceptions\RuntimeError;
use Phortugol\Token;


// TODO: Inject a buffer on constructor to mock echo
class ErrorHelper {
    public bool $hadError = false;
    public bool $hadRuntimeError = false;

    public function report(int $line, string $message): void
    {
        echo 'Erro na linha ' . $line . ': ' . $message . PHP_EOL;
        $this->hadError = true;
    }

    public function error(Token $token, string $message): void
    {
        if ($token->kind == TokenType::EOF) {
            $this->report($token->line, " no fim " . $message);
        } else {
            $this->report($token->line, "em '" . $token->lexeme . "'  " . $message);
        }
    }

    public function runtimeError(RuntimeError $error): void
    {
        echo "Erro de runtime: {$error->getMessage()}\n[linha {$error->token->line}]\n";
        $this->hadRuntimeError = true;
    }
}
