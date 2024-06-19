<?php

namespace Toyjs\Toyjs\Helpers;

// TODO: Inject a buffer on constructor to mock echo
class ErrorHelper {
    public bool $hadError = false;

    public function report(int $line, string $message): void
    {
        echo 'Error at line ' . $line . ': ' . $message . PHP_EOL;
        $this->hadError = true;
    }
}
