<?php

namespace Phortugol\Interpreter;

use Phortugol\Enums\TokenType;
use Phortugol\Exceptions\RuntimeError;
use Phortugol\Expr\BinaryExpr;
use Phortugol\Expr\ConditionalExpr;
use Phortugol\Expr\ExprHandler;
use Phortugol\Expr\GroupingExpr;
use Phortugol\Expr\LiteralExpr;
use Phortugol\Expr\UnaryExpr;
use Phortugol\Helpers\ErrorHelper;
use Phortugol\Stmt\ExpressionStmt;
use Phortugol\Stmt\PrintStmt;
use Phortugol\Stmt\Stmt;
use Phortugol\Stmt\StmtHandler;

class Interpreter
{
    /** @use ExprHandler<mixed> */
    use ExprHandler;

    /** @use StmtHandler<void> */
    use StmtHandler;

    private readonly ErrorHelper $errorHelper;
    private readonly TypeValidator $typeValidator;

    public function __construct(ErrorHelper $errorHelper)
    {
        $this->errorHelper = $errorHelper;
        $this->typeValidator = new TypeValidator();
    }
    /**
     * @param Stmt[] $statements
     */
    public function interpret(array $statements): void
    {
        try {
            foreach($statements as $statement) {
                $this->execute($statement);
            }
        } catch (RuntimeError $e) {
            $this->errorHelper->runtimeError($e);
        }
    }

    protected function handleBinary(BinaryExpr $expr): mixed
    {
        $left = $this->evaluate($expr->left);
        $right = $this->evaluate($expr->right);

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
        $result = $this->evaluate($expr->right);
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
        return $this->evaluate($expr->expression);
    }

    protected function handleLiteral(LiteralExpr $expr): mixed
    {
        return $expr->value;
    }

    protected function handleConditional(ConditionalExpr $expr): mixed
    {
        $condition = $this->evaluate($expr->condition);
        if ($condition) {
            return $this->evaluate($expr->trueExpr);
        }

        return $this->evaluate($expr->falseExpr);
    }

    // Statements
    protected function handlePrint(PrintStmt $stmt): void
    {
        $result = $this->evaluate($stmt->expression);

        if ($result === true) {
            echo "verdadeiro";
        } else if ($result === false) {
            echo "falso";
        }
        else {
            echo $result;
        }

        echo PHP_EOL;
    }

    protected function handleExpression(ExpressionStmt $stmt): void
    {
        $this->evaluate($stmt->expression);
    }
}
