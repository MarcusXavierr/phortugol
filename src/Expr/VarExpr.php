<?php

namespace Phortugol\Expr;

use Phortugol\Token;

class VarExpr extends Expr
{
    public function __construct(public readonly Token $name){}
}
