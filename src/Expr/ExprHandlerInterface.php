<?php

namespace Toyjs\Toyjs\Expr;

/**
 * @template T
 */
interface ExprHandlerInterface
{
    /**
     * @return T
     */
    public function handle(Expr $expr);

    /**
     * @return T
     */
    public function handleBinary(BinaryExpr $expr);

    /**
     * @return T
     */
    public function handleUnary(UnaryExpr $expr);

    /**
     * @return T
     */
    public function handleGrouping(GroupingExpr $expr);

    /**
     * @return T
     */
    public function handleLiteral(LiteralExpr $expr);
}
