<?php

namespace Phortugol\Interpreter;

use Ds\Map;
use Phortugol\Enums\TokenType;
use Phortugol\Exceptions\RuntimeError;
use Phortugol\Expr\ArrayDefExpr;
use Phortugol\Expr\ArrayGetExpr;
use Phortugol\Expr\ArraySetExpr;
use Phortugol\Expr\AssignExpr;
use Phortugol\Expr\BinaryExpr;
use Phortugol\Expr\CallExpr;
use Phortugol\Expr\ConditionalExpr;
use Phortugol\Expr\Expr;
use Phortugol\Expr\ExprHandler;
use Phortugol\Expr\GetExpr;
use Phortugol\Expr\GroupingExpr;
use Phortugol\Expr\LambdaExpr;
use Phortugol\Expr\LiteralExpr;
use Phortugol\Expr\LogicalExpr;
use Phortugol\Expr\SetExpr;
use Phortugol\Expr\ThisExpr;
use Phortugol\Expr\UnaryExpr;
use Phortugol\Expr\VarExpr;
use Phortugol\Helpers\ErrorHelper;
use Phortugol\NativeFunctions\Instance;
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

    protected function handleArrayDefExpr(ArrayDefExpr $expr): mixed
    {
        $elements = new Map();
        foreach ($expr->elements as $key => $element) {
            if (!is_scalar($key)) {
                // INFO: Todas as chaves de arrays são convertidas para strings
                throw new RuntimeError($expr->leftBracket, "Índices de arrays só podem ser números ou strings");
            }

            $elements->put((string)$key, $this->evaluate($element));
        }

        return $elements;
    }

    protected function handleArrayGetExpr(ArrayGetExpr $expr): mixed
    {
        $array = $this->evaluate($expr->array);
        $index = $this->evaluate($expr->index);

        if (!($array instanceof Map)) {
            throw new RuntimeError($expr->bracket, "Variável não é um array");
        }

        if (!is_scalar($index)) {
            throw new RuntimeError($expr->bracket, "Índices de arrays só podem ser números ou strings");
        }

        if ($array->hasKey((string)$index)) {
            return $array[(string)$index];
        }

        throw new RuntimeError($expr->bracket, "Acessando um índice inexistente no array");
    }

    protected function handleArraySetExpr(ArraySetExpr $expr): mixed
    {
        $array = $this->evaluate($expr->array);
        $index = $this->evaluate($expr->index);

        if (!($array instanceof Map)) {
            throw new RuntimeError($expr->bracket, "Variável não é um array");
        }

        if (!is_scalar($index)) {
            throw new RuntimeError($expr->bracket, "Índices de arrays só podem ser números ou strings");
        }

        $array->put((string)$index, $this->evaluate($expr->assignment));

        return $array;
    }

    protected function handleGetExpr(GetExpr $expr): mixed
    {
        $object = $this->evaluate($expr->object);
        // Caso o meu objeto seja algo que implemente o contrato instancia, eu retorno isso.
        if ($object instanceof Instance) {
            return $object->get($expr->name);
        }

        throw new RuntimeError($expr->name, "Somente objetos têm propriedades.");
    }

    protected function handleSetExpr(SetExpr $expr): mixed
    {
        $object = $this->evaluate($expr->object);
        if (!($object instanceof Instance)) {
            throw new RuntimeError($expr->name, "Somente objetos têm propriedades");
        }

        $value = $this->evaluate($expr->value);
        $object->set($expr->name, $value);
        return $value;
    }

    protected function handleThisExpr(ThisExpr $expr): mixed
    {
        return $this->lookupVariable($expr->keyword, $expr);
    }

    private function lookupVariable(Token $name, Expr $expr): mixed
    {
        $distance = $this->interpreter->locals->get($expr, null);
        if ($distance !== null) {
            return $this->environment->getAt($distance, $name->lexeme);
        } else {
            return $this->interpreter->globals->get($name);
        }
    }
}
