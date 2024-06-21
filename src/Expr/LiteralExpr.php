<?php

namespace Phortugol\Expr;

class LiteralExpr extends Expr
{
    public function __construct(
        public readonly mixed $value
    ){}
}
