<?php

namespace Phortugol\Stmt;

class BlockStmt extends Stmt
{
    /**
     * @param Stmt[] $declarations
     */
    public function __construct(
        public readonly array $declarations
    ){}
}
