<?php

namespace Phortugol\Stmt;

use Phortugol\Token;

class ClassDecl extends Stmt
{
    /**
     * @param Stmt[] $body
     */
    public function __construct(
        public readonly Token $name,
        public readonly array $body
    ){}
}
