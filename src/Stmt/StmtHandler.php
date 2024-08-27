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
            $stmt instanceof WhileStmt => $this->handleWhile($stmt),
            $stmt instanceof BreakStmt => $this->handleBreak(),
            $stmt instanceof ContinueStmt => $this->handleContinue(),
            $stmt instanceof FunctionStmt => $this->handleFunctionStmt($stmt),
            $stmt instanceof ReturnStmt => $this->handleReturnStmt($stmt),
            $stmt instanceof ClassDecl => $this->handleClassDecl($stmt),
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

    /**
     * @return T
     */
    protected abstract function handleWhile(WhileStmt $stmt);

    /**
     * @return T
     */
    protected abstract function handleBreak();

    /**
     * @return T
     */
    protected abstract function handleContinue();

    /**
     * @return T
     */
    protected abstract function handleFunctionStmt(FunctionStmt $stmt);

    /**
     * @return T
     */
    protected abstract function handleReturnStmt(ReturnStmt $stmt);

    /**
     * @return T
     */
    protected abstract function handleClassDecl(ClassDecl $stmt);
}
