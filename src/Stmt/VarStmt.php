<?php

namespace Phortugol\Stmt;

use Phortugol\Expr\Expr;

class VarStmt extends Stmt
{
    public function __construct(
        public readonly string $identifier,
        public readonly ?Expr $initializer,
    ){}
}
