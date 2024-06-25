<?php

namespace Phortugol\Stmt;

use Phortugol\Expr\Expr;

class ExpressionStmt extends Stmt
{
    public function __construct(public readonly Expr $expression){}
}
