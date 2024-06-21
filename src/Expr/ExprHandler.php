<?php

namespace Toyjs\Toyjs\Expr;

use Exception;


/**
 * @template T
 */
abstract class ExprHandler
{
    /**
     * @return T
     */
    public function handle(Expr $expr): mixed {
        return match(true) {
            $expr instanceof BinaryExpr => $this->handleBinary($expr),
            $expr instanceof UnaryExpr => $this->handleUnary($expr),
            $expr instanceof LiteralExpr => $this->handleLiteral($expr),
            $expr instanceof GroupingExpr => $this->handleGrouping($expr),
            $expr instanceof ConditionalExpr => $this->handleConditional($expr),
            default => throw new Exception("Incomplete implementation")
        };
    }

    /**
     * @return T
     */
    public abstract function handleBinary(BinaryExpr $expr);

    /**
     * @return T
     */
    public abstract function handleUnary(UnaryExpr $expr);

    /**
     * @return T
     */
    public abstract function handleLiteral(LiteralExpr $expr);

    /**
     * @return T
     */
    public abstract function handleGrouping(GroupingExpr $expr);

    /**
     * @return T
     */
    public abstract function handleConditional(ConditionalExpr $expr);
}
