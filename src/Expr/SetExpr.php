<?php

namespace Phortugol\Expr;

use Phortugol\Token;

class SetExpr extends Expr
{
    public function __construct(
        public readonly Expr $object,
        public readonly Token $name,
        public readonly Expr $value,
    ){}
}
