<?php

namespace Toyjs\Toyjs\Expr;

class GroupingExpr extends Expr
{
    public function __construct(
        public readonly Expr $expression
    ){}
}
