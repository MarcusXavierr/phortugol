<?php

namespace Toyjs\Toyjs\Expr;

use Toyjs\Toyjs\Token;

class UnaryExpr extends Expr
{
    public function __construct(
        public readonly Token $token,
        public readonly Expr $right
    ){}
}
