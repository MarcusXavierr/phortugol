<?php

namespace Phortugol\Expr;

use Phortugol\Token;

class AssignExpr extends Expr
{
    public function __construct(
        public readonly Token $identifier,
        public readonly Expr $assignment
    ) {}
}
