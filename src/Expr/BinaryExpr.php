<?php

namespace Phortugol\Expr;

use Phortugol\Token;

class BinaryExpr extends Expr
{
    public function __construct(
        public readonly Expr $left,
        public readonly Token $token,
        public readonly Expr $right
    ){}
}
