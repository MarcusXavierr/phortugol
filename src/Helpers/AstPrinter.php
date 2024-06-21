<?php

namespace Phortugol\Helpers;

use Phortugol\Expr\BinaryExpr;
use Phortugol\Expr\ConditionalExpr;
use Phortugol\Expr\Expr;
use Phortugol\Expr\ExprHandler;
use Phortugol\Expr\GroupingExpr;
use Phortugol\Expr\LiteralExpr;
use Phortugol\Expr\UnaryExpr;

/**
 * @extends ExprHandler<string>
*/
class AstPrinter extends ExprHandler
{
    public function print(Expr $expression): string {
       return $this->handle($expression);
    }

    public function handleBinary(BinaryExpr $expr): string {
        return $this->parenthesize($expr->token->lexeme, $expr->left, $expr->right);
    }

    public function handleUnary(UnaryExpr $expr): string {
        return $this->parenthesize($expr->token->lexeme, $expr->right);
    }

    public function handleLiteral(LiteralExpr $expr): string {
        if ($expr->value === null) return "nulo";
        return $expr->value;
    }

    public function handleGrouping(GroupingExpr $expr): string {
        return $this->handle($expr->expression);
    }

    public function handleConditional(ConditionalExpr $expr): string {
        return $this->parenthesize("if", $expr->condition, $expr->trueExpr, $expr->falseExpr);
    }

    private function parenthesize(string $name, Expr ...$exprs): string {
        $builder = "(" . $name;
        foreach ($exprs as $expr) {
            $builder .= " " . $this->handle($expr);
        }
        $builder .= ")";

        return $builder;
    }
}
