<?php

namespace Phortugol\Expr;

use Phortugol\Token;

class ThisExpr extends Expr
{
    public function __construct(public readonly Token $keyword){}
}
