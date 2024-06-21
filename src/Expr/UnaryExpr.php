<?php

namespace Phortugol\Expr;

use Phortugol\Token;

class UnaryExpr extends Expr
{
    public function __construct(
        public readonly Token $token,
        public readonly Expr $right
    ){}
}
