<?php

namespace Phortugol\Stmt;

use Phortugol\Expr\Expr;

class IfStmt extends Stmt
{
    public function __construct(
        public readonly Expr $condition,
        public readonly Stmt $thenBranch,
        public readonly Stmt|null $elseBranch
    ){}
}
