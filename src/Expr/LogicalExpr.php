<?php

namespace Phortugol\Expr;

use Phortugol\Token;

class LogicalExpr extends Expr
{
    public function __construct(
        public readonly Expr $left,
        public readonly Token $operator,
        public readonly Expr $right,
    ){}
}
