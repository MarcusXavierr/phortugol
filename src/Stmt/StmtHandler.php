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
            $stmt instanceof VarStmt => $this->handleVarStmt($stmt),
            $stmt instanceof BlockStmt => $this->handleBlockStmt($stmt),
            $stmt instanceof IfStmt => $this->handleIf($stmt),
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

    /**
     * @return T
     */
    protected abstract function handleVarStmt(VarStmt $stmt);

    /**
     * @return T
     */
    protected abstract function handleBlockStmt(BlockStmt $stmt);

    /**
     * @return T
     */
    protected abstract function handleIf(IfStmt $stmt);
}
