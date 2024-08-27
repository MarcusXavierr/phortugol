<?php

namespace Phortugol\Expr;

use Phortugol\Token;

class GetExpr extends Expr
{
    public function __construct(
        public readonly Expr $object,
        public readonly Token $name
    ){}
}
