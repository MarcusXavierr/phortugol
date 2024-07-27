<?php

namespace Phortugol\Stmt;

use Phortugol\Token;

class FunctionStmt extends Stmt
{
    /**
     * @param Token[] $parameters
     * @param Stmt[] $body
     */
    public function __construct(
        public readonly Token $name,
        public readonly array $parameters,
        public readonly array $body
    ){}
}
