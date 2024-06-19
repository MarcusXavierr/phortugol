<?php

namespace Toyjs\Toyjs\Expr;

class ConditionalExpr
{
    public function __construct(
        public readonly Expr $condition,
        public readonly Expr $trueExpr,
        public readonly Expr $falseExpr
    ){}
}
