<?php

namespace Phortugol\Expr;

class LambdaExpr extends Expr
{
    /**
     * @param Token[] $parameters
     * @param Stmt[] $body
     */
    public function __construct(
        public readonly array $parameters,
        public readonly array $body
    ) {}
}
