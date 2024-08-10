<?php

namespace Phortugol\Interpreter;

use Phortugol\Enums\TokenType;
use Phortugol\Exceptions\RuntimeError;
use Phortugol\Expr\AssignExpr;
use Phortugol\Expr\BinaryExpr;
use Phortugol\Expr\CallExpr;
use Phortugol\Expr\ConditionalExpr;
use Phortugol\Expr\ExprHandler;
use Phortugol\Expr\GroupingExpr;
use Phortugol\Expr\LambdaExpr;
use Phortugol\Expr\LiteralExpr;
use Phortugol\Expr\LogicalExpr;
use Phortugol\Expr\UnaryExpr;
use Phortugol\Expr\VarExpr;
use Phortugol\Helpers\ErrorHelper;
use Phortugol\NativeFunctions\PhortugolFunction;
use Phortugol\Token;

class ExprInterpreter
{
    /** @use ExprHandler<mixed> */
    use ExprHandler;

    private readonly ErrorHelper $errorHelper;
    private readonly TypeValidator $typeValidator;
    private Environment $environment;
    private readonly Interpreter $interpreter;

    public function __construct(ErrorHelper $errorHelper, Environment $environment, Interpreter $interpreter)
    {
        $this->interpreter = $interpreter;
        $this->errorHelper = $errorHelper;
        $this->typeValidator = new TypeValidator();
        $this->environment = $environment;
    }

    public function setEnvironment(Environment $environment): void
    {
        $this->environment = $environment;
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
                if (is_string($left) || is_string($right)) {
                    return $left . $right;
                };
                throw new RuntimeError($expr->token, "Os operandos precisam ser ambos números ou um deles precisa ser uma string");

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

    protected function handleVarExpr(VarExpr $expr): mixed
    {
        return $this->lookupVariable($expr->name, $expr);
    }

    protected function handleAssignExpr(AssignExpr $expr): mixed
    {
        $value = $this->evaluate($expr->assignment);
        $distance = $this->interpreter->locals->get($expr, null);

        if ($distance !== null) {
            $this->environment->assignAt($distance, $expr->identifier, $value);
        } else {
            $this->interpreter->globals->assign($expr->identifier, $value);
        }

        return $value;
    }

    protected function handleLogicalExpr(LogicalExpr $expr): mixed
    {
        $left = $this->evaluate($expr->left);
        if ($expr->operator->kind == TokenType::OR) {
            if ($left) return $left;
        } else {
            if (!$left) return $left;
        }

        return $this->evaluate($expr->right);
    }

    protected function handleCallExpr(CallExpr $expr): mixed
    {
        $callee = $this->evaluate($expr->callee);

        $arguments = [];
        foreach ($expr->arguments as $argument) {
            array_push($arguments, $this->evaluate($argument));
        }

        if ($callee instanceof PhortCallable) {
            $countArguments = count($arguments);
            if ($countArguments != $callee->arity()) {
                throw new RuntimeError($expr->paren, "Experado {$callee->arity()} argumentos, mas recebi {$countArguments} argumento(s).");
            }
            return $callee->call($this->interpreter, $arguments);
        }

        throw new RuntimeError($expr->paren, "Só é possível chamar funções ou classes");
    }

    protected function handleLambdaExpr(LambdaExpr $expr): mixed
    {
        return new PhortugolFunction($expr, $this->interpreter->environment);
    }

    private function lookupVariable(Token $name, VarExpr $expr): mixed
    {
        $distance = $this->interpreter->locals->get($expr, null);
        if ($distance !== null) {
            return $this->environment->getAt($distance, $name->lexeme);
        } else {
            return $this->interpreter->globals->get($name);
        }
    }
}
