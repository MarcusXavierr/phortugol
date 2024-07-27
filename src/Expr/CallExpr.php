<?php

namespace Phortugol\Expr;

use Phortugol\Token;

class CallExpr extends Expr
{
    /**
     * @param Expr[] $arguments
     */
    public function __construct(
        public readonly Expr $callee,
        public readonly Token $paren,
        public readonly array $arguments
    ){}
}
