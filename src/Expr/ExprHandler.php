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
}
