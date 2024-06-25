<?php

namespace Phortugol\Stmt;

use Exception;


/**
 * @template T
 */
trait StmtHandler
{
    /**
     * @return T
     */
    public function execute(Stmt $stmt): mixed
    {
        return match(true) {
            $stmt instanceof PrintStmt => $this->handlePrint($stmt),
            $stmt instanceof ExpressionStmt => $this->handleExpression($stmt),
            default => throw new Exception("Incomplete statement implementation")
        };
    }

    /**
     * @return T
     */
    protected abstract function handlePrint(PrintStmt $stmt);

    /**
     * @return T
     */
    protected abstract function handleExpression(ExpressionStmt $stmt);
}
