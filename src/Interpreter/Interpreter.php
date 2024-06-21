<?php

namespace Phortugol\Interpreter;

use Phortugol\Enums\TokenType;
use Phortugol\Exceptions\RuntimeError;
use Phortugol\Expr\BinaryExpr;
use Phortugol\Expr\ConditionalExpr;
use Phortugol\Expr\Expr;
use Phortugol\Expr\ExprHandler;
use Phortugol\Expr\GroupingExpr;
use Phortugol\Expr\LiteralExpr;
use Phortugol\Expr\UnaryExpr;
use Phortugol\Helpers\ErrorHelper;

/**
 * @extends ExprHandler<mixed>
*/
class Interpreter extends ExprHandler
{
    private readonly ErrorHelper $errorHelper;
    private readonly TypeValidator $typeValidator;

    public function __construct(ErrorHelper $errorHelper)
    {
        $this->errorHelper = $errorHelper;
        $this->typeValidator = new TypeValidator();
    }

    public function interpret(Expr $expr): void
    {
        try {
            $result = $this->handle($expr);
            if ($result === true) {
                 $result = "verdadeiro";
            } else if ($result === false) {
                $result = "falso";
            }

            echo $result . PHP_EOL;
        } catch (RuntimeError $e) {
            $this->errorHelper->runtimeError($e);
        }
    }

    protected function handleBinary(BinaryExpr $expr): mixed
    {
        $left = $this->handle($expr->left);
        $right = $this->handle($expr->right);

        if ($this->typeValidator->shouldBeNumeric($expr->token->kind)) {
            $this->typeValidator->validateIsNumber($expr->token, $left, $right);
        }

        // TODO: maybe I can break this switch into functions
        switch($expr->token->kind) {
            case TokenType::MINUS:
                return $left - $right;
            case TokenType::STAR:
                return $left * $right;
            case TokenType::MODULO:
                return $left % $right;
            case TokenType::SLASH:
                if ($right == 0) {
                    throw new RuntimeError($expr->token, "O divisor deve ser diferente de zero");
                }
                return $left / $right;
            case TokenType::PLUS:
                if (is_string($left) && is_string($right)) {
                    return $left . $right;
                }
                if (is_numeric($left) && is_numeric($right)) {
                    return $left + $right;
                }
                throw new RuntimeError($expr->token, "Os operandos precisam ser ambos números ou strings");

            // Logical operators
            case TokenType::AND:
                return $left && $right;
            case TokenType::OR:
                return $left || $right;

            // Comparison operators
            case TokenType::GREATER:
                return $left > $right;
            case TokenType::GREATER_EQUAL:
                return $left >= $right;
            case TokenType::LESS:
                return $left < $right;
            case TokenType::LESS_EQUAL:
                return $left <= $right;

            // Equality operators
            case TokenType::EQUAL_EQUAL:
                return $left == $right;
            case TokenType::BANG_EQUAL:
                return $left != $right;
        }

        return null;
    }

    protected function handleUnary(UnaryExpr $expr): mixed
    {
        $result = $this->handle($expr->right);
        switch($expr->token->kind) {
            case TokenType::MINUS:
                $this->typeValidator->validateIsNumber($expr->token, $result);
                return -$result;
            case TokenType::BANG:
                return !$result;
        }

        return null;
    }

    protected function handleGrouping(GroupingExpr $expr): mixed
    {
        return $this->handle($expr->expression);
    }

    protected function handleLiteral(LiteralExpr $expr): mixed
    {
        return $expr->value;
    }

    protected function handleConditional(ConditionalExpr $expr): mixed
    {
        $condition = $this->handle($expr->condition);
        if ($condition) {
            return $this->handle($expr->trueExpr);
        }

        return $this->handle($expr->falseExpr);
    }

}
