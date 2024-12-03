<?php

namespace Phortugol\Expr;

use Phortugol\Token;

class ArraySetExpr extends Expr
{
    public function __construct(
        public readonly Token $bracket,
        public readonly Expr $array,
        public readonly Expr $index,
        public readonly Expr $assignment
    ){}
}
