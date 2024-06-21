<?php

namespace Phortugol\Expr;

class ConditionalExpr extends  Expr
{
    public function __construct(
        public readonly Expr $condition,
        public readonly Expr $trueExpr,
        public readonly Expr $falseExpr
    ){}
}
