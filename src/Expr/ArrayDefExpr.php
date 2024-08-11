<?php

namespace Phortugol\Expr;

use Phortugol\Token;

class ArrayDefExpr extends Expr
{
    /**
     * @param Expr[] $elements
     */
    public function __construct(
        public readonly Token $leftBracket,
        public readonly array $elements,
    ){}
}
