<?php

namespace Phortugol\Stmt;

use Phortugol\Expr\Expr;
use Phortugol\Token;

class ReturnStmt extends  Stmt
{
    public function __construct(
        public readonly Token $keyword,
        public readonly ?Expr $value
    ){}
}
