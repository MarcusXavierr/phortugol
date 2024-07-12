<?php

namespace Phortugol\Stmt;

use Phortugol\Expr\Expr;

class WhileStmt extends Stmt
{
    public function __construct(
        public readonly Expr $condition,
        public readonly Stmt $body,
        public readonly ?Stmt $fallbackIncrement = null
    ){}
}
