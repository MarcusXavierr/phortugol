#!/usr/bin/env php
<?php

if (file_exists(__DIR__ . '/../../autoload.php')) {
    require __DIR__ . '/../../autoload.php';
} else {
    require __DIR__ . '/vendor/autoload.php';
}

use Toyjs\Toyjs\Helpers\ErrorHelper;
use \Toyjs\Toyjs\Scanner\Scanner;

class Main {
    private ErrorHelper $error;
    public function __construct()
    {
        $this->error = new ErrorHelper();
    }
    /**
     * @param array<int,mixed> $argv
     */
    public function main(array $argv): void
    {
        if (count($argv) > 2) {
            echo "usage: ./main.php <source_file>" . PHP_EOL;
            exit(1);
        }

        if (isset($argv[1])) {
            $this->runFile($argv[1]);
            exit(0);
        }

        $this->runPrompt();
    }

    private function runPrompt(): void
    {
        while (true) {
            $line = readline("> ");
            if ($line == null) break;
            $this->run($line);
            $this->error->hadError = false;
        }
    }

    private function runFile(string $path): void
    {
        $fileContent = file_get_contents($path);
        $this->run($fileContent);

        if ($this->hadError) {
            exit(65);
        }
    }

    private function run(string $source): void
    {
        $scanner = new Scanner($source, $this->error);
        $tokens = $scanner->scanTokens();
        if ($this->error->hadError) {
            return;
        }

        foreach ($tokens as $token) {
            echo $token->toString() . PHP_EOL;
        }
    }

    public static function error(int $line, string $message): void
    {
    }
}

$main = new Main();
/* @var $argv array */
$main->main($argv);
