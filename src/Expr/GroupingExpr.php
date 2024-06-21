<?php

namespace Phortugol\Expr;

class GroupingExpr extends Expr
{
    public function __construct(
        public readonly Expr $expression
    ){}
}
