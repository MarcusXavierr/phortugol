<?php

namespace Phortugol\Interpreter;

use PHPUnit\Runner\ErrorHandler;
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
use Phortugol\Token;

/**
 * @extends ExprHandler<mixed>
*/
class Interpreter extends ExprHandler
{
    private readonly ErrorHelper $errorHelper;

    public function __construct(ErrorHelper $errorHelper)
    {
        $this->errorHelper = $errorHelper;
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

    // TODO: Create a function to validate the operand type based on TokenType
    public function handleBinary(BinaryExpr $expr): mixed
    {
        $left = $this->handle($expr->left);
        $right = $this->handle($expr->right);

        switch($expr->token->kind) {
            case TokenType::MINUS:
                validateIsNumber($expr->token, $left, $right);
                return $left - $right;
            case TokenType::STAR:
                validateIsNumber($expr->token, $left, $right);
                return $left * $right;
            case TokenType::MODULO:
                validateIsNumber($expr->token, $left, $right);
                return $left % $right;
            case TokenType::SLASH:
                validateIsNumber($expr->token, $left, $right);
                return $left / $right;
            case TokenType::PLUS:
                if (is_string($left) && is_string($right)) {
                    return $left . $right;
                }
                if (is_numeric($left) && is_numeric($right)) {
                    return $left + $right;
                }
                throw new RuntimeError($expr->token, "Os operandos precisam ser ambos números ou strings");
                break;

            case TokenType::AND:
                return $left && $right;
            case TokenType::OR:
                return $left || $right;

            case TokenType::GREATER:
                validateIsNumber($expr->token, $left, $right);
                return $left > $right;
            case TokenType::GREATER_EQUAL:
                validateIsNumber($expr->token, $left, $right);
                return $left >= $right;
            case TokenType::LESS:
                validateIsNumber($expr->token, $left, $right);
                return $left < $right;
            case TokenType::LESS_EQUAL:
                validateIsNumber($expr->token, $left, $right);
                return $left <= $right;

            case TokenType::EQUAL_EQUAL:
                return $left == $right;
            case TokenType::BANG_EQUAL:
                return $left != $right;
        }

        return null;
    }

    public function handleUnary(UnaryExpr $expr): mixed
    {
        $result = $this->handle($expr->right);
        switch($expr->token->kind) {
            case TokenType::MINUS:
                validateIsNumber($expr->token, $result);
                return -$result;
            case TokenType::BANG:
                return !$result;
        }

        return null;
    }

    public function handleGrouping(GroupingExpr $expr): mixed
    {
        return $this->handle($expr->expression);
    }

    public function handleLiteral(LiteralExpr $expr): mixed
    {
        return $expr->value;
    }

    public function handleConditional(ConditionalExpr $expr): mixed
    {
        $condition = $this->handle($expr->condition);
        if ($condition) {
            return $this->handle($expr->trueExpr);
        }

        return $this->handle($expr->falseExpr);
    }

}

function validateIsNumber(Token $operand, mixed ...$values): void
{
    foreach ($values as $value) {
        if (!is_numeric($value)) {
            throw new RuntimeError($operand, "O operador '{$operand->lexeme}' espera um número");
        }
    }
}
