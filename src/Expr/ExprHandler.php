<?php

namespace Phortugol\Expr;

use Exception;


/**
 * @template T
 */
trait ExprHandler
{
    /**
     * @return T
     */
    public function evaluate(Expr $expr): mixed {
        return match(true) {
            $expr instanceof BinaryExpr => $this->handleBinary($expr),
            $expr instanceof UnaryExpr => $this->handleUnary($expr),
            $expr instanceof LiteralExpr => $this->handleLiteral($expr),
            $expr instanceof GroupingExpr => $this->handleGrouping($expr),
            $expr instanceof ConditionalExpr => $this->handleConditional($expr),
            $expr instanceof VarExpr => $this->handleVarExpr($expr),
            $expr instanceof AssignExpr => $this->handleAssignExpr($expr),
            $expr instanceof LogicalExpr => $this->handleLogicalExpr($expr),
            $expr instanceof CallExpr => $this->handleCallExpr($expr),
            $expr instanceof LambdaExpr => $this->handleLambdaExpr($expr),
            $expr instanceof ArrayDefExpr => $this->handleArrayDefExpr($expr),
            $expr instanceof ArrayGetExpr => $this->handleArrayGetExpr($expr),
            $expr instanceof GetExpr => $this->handleGetExpr($expr),
            $expr instanceof SetExpr => $this->handleSetExpr($expr),
            $expr instanceof ThisExpr => $this->handleThisExpr($expr),
            default => throw new Exception("Incomplete expression implementation")
        };
    }

    /**
     * @return T
     */
    protected abstract function handleBinary(BinaryExpr $expr);

    /**
     * @return T
     */
    protected abstract function handleUnary(UnaryExpr $expr);

    /**
     * @return T
     */
    protected abstract function handleLiteral(LiteralExpr $expr);

    /**
     * @return T
     */
    protected abstract function handleGrouping(GroupingExpr $expr);

    /**
     * @return T
     */
    protected abstract function handleConditional(ConditionalExpr $expr);

    /**
     * @return T
     */
    protected abstract function handleVarExpr(VarExpr $expr);

    /**
     * @return T
     */
    protected abstract function handleAssignExpr(AssignExpr $expr);

    /**
     * @return T
     */
    protected abstract function handleLogicalExpr(LogicalExpr $expr);

    /**
     * @return T
     */
    protected abstract function handleCallExpr(CallExpr $expr);

    /**
     * @return T
     */
    protected abstract function handleLambdaExpr(LambdaExpr $expr);

    /**
     * @return T
     */
    protected abstract function handleArrayDefExpr(ArrayDefExpr $expr);

    /**
     * @return T
     */
    protected abstract function handleArrayGetExpr(ArrayGetExpr $expr);

    /**
     * @return T
     */
    protected abstract function handleGetExpr(GetExpr $expr);

    /**
     * @return T
     */
    protected abstract function handleSetExpr(SetExpr $expr);

    /**
     * @return T
     */
    protected abstract function handleThisExpr(ThisExpr $expr);
}
