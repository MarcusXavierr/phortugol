<?php

namespace Toyjs\Toyjs\Expr;

use Toyjs\Toyjs\Token;

class BinaryExpr extends Expr
{
    public function __construct(
        public readonly Expr $left,
        public readonly Token $token,
        public readonly Expr $right
    ){}
}
