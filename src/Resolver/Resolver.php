<?php

namespace Phortugol\Resolver;

use Ds\Map;
use Ds\Stack;
use Phortugol\Enums\TokenType;
use Phortugol\Expr\ArrayDefExpr;
use Phortugol\Expr\ArrayGetExpr;
use Phortugol\Expr\ArraySetExpr;
use Phortugol\Expr\AssignExpr;
use Phortugol\Expr\Expr;
use Phortugol\Expr\ExprHandler;
use Phortugol\Expr\GetExpr;
use Phortugol\Expr\SetExpr;
use Phortugol\Expr\ThisExpr;
use Phortugol\Expr\VarExpr;
use Phortugol\Expr\BinaryExpr;
use Phortugol\Expr\UnaryExpr;
use Phortugol\Expr\LiteralExpr;
use Phortugol\Expr\GroupingExpr;
use Phortugol\Expr\ConditionalExpr;
use Phortugol\Expr\LogicalExpr;
use Phortugol\Expr\CallExpr;
use Phortugol\Expr\LambdaExpr;
use Phortugol\Helpers\ErrorHelper;
use Phortugol\Interpreter\Interpreter;
use Phortugol\Stmt\BlockStmt;
use Phortugol\Stmt\ClassDecl;
use Phortugol\Stmt\ExpressionStmt;
use Phortugol\Stmt\FunctionStmt;
use Phortugol\Stmt\IfStmt;
use Phortugol\Stmt\PrintStmt;
use Phortugol\Stmt\ReturnStmt;
use Phortugol\Stmt\Stmt;
use Phortugol\Stmt\StmtHandler;
use Phortugol\Stmt\VarStmt;
use Phortugol\Stmt\WhileStmt;
use Phortugol\Token;

class Resolver
{
    /** @use StmtHandler<void> */
    use StmtHandler;

    /** @use ExprHandler<void> */
    use ExprHandler;

    /** @var Stack<Map> $scopes */
    private readonly Stack $scopes;
    private readonly Interpreter $interpreter;
    private readonly ErrorHelper $errorHelper;
    private FunctionType $currentFunction;
    private ClassType $currentClass;

    public function __construct(Interpreter $interpreter, ErrorHelper $error)
    {
        $this->interpreter = $interpreter;
        $this->errorHelper = $error;
        $this->scopes = new Stack();
        $this->currentFunction = FunctionType::NONE;
        $this->currentClass = ClassType::NONE;
    }

    protected function handleBlockStmt(BlockStmt $stmt): void
    {
        $this->beginScope();
        $this->resolve($stmt->declarations);
        $this->endScope();
    }

    protected function handleVarStmt(VarStmt $stmt): void
    {
        $this->declare($stmt->identifier);
        if ($stmt->initializer != null) {
            $this->resolveExpr($stmt->initializer);
        }
        $this->define($stmt->identifier);
    }

    protected function handleVarExpr(VarExpr $expr): void
    {
        if ($this->scopes->isEmpty()) {
            return;
        }

        $name = $expr->name->lexeme;

        if(isset($this->scopes->peek()[$name]) && $this->scopes->peek()->get($name) === false) {
            $this->errorHelper->error($expr->name, "Não é possível ler a propria variável como seu inicializador");
        }

        $this->resolveLocal($expr, $expr->name);
    }

    protected function handleAssignExpr(AssignExpr $expr): void
    {
        $this->resolveExpr($expr->assignment);

        $this->resolveLocal($expr, $expr->identifier);
    }

    protected function handleFunctionStmt(FunctionStmt $stmt): void
    {
        $this->declare($stmt->name->lexeme);
        $this->define($stmt->name->lexeme);

        $this->resolveFunction($stmt, FunctionType::FUNCTION);
    }

    /**
     * @param Stmt[] $statements
     */
    public function resolve(array $statements): void
    {
        foreach ($statements as $stmt) {
            $this->resolveStmt($stmt);
        }
    }

    private function resolveStmt(Stmt $stmt): void
    {
        $this->execute($stmt);
    }

    private function resolveExpr(Expr $expr): void
    {
        $this->evaluate($expr);
    }

    private function beginScope(): void
    {
        $this->scopes->push(new Map());
    }

    private function endScope(): void
    {
        $this->scopes->pop();
    }

    // INFO: Talvez quebre pq eu to usando uma string e não token
    private function declare(string $name): void
    {
        if($this->scopes->isEmpty()) return;

        $scope = $this->scopes->peek();
        if (isset($scope[$name])) {
            // TODO: injetar um token no VarStmt, pois eu vou precisar aqui pra fazer o error handling correto
            // $this->errorHelper->error($name, $message);
            $this->errorHelper->report(1, "Variável {$name} já definida");
        }

        $this->scopes->peek()->put($name, false);
    }

    private function define(string $name): void
    {
        if($this->scopes->isEmpty()) return;

        $this->scopes->peek()->put($name, true);
    }

    private function resolveLocal(Expr $expr, Token $name): void
    {
        for($i = 0; $i < $this->scopes->count(); $i++) {
            // Here, we take the numbers of steps until we reach the end. So array[0] is equal the last item of stack
            $actualScope = $this->scopes->toArray()[$i];
            if (isset($actualScope[$name->lexeme])) {
                $this->interpreter->resolve($expr, $i);
            }
        }
    }

    private function resolveFunction(FunctionStmt $stmt, FunctionType $type): void
    {
        $enclosingFunction = $this->currentFunction;
        $this->currentFunction = $type;

        $this->beginScope();
        foreach ($stmt->parameters as $param) {
            $this->declare($param->lexeme);
            $this->define($param->lexeme);
        }

        $this->resolve($stmt->body);
        $this->endScope();

        $this->currentFunction = $enclosingFunction;
    }


    // Other tree nodes that we need to implement in order to be able to traverse the tree
    protected function handleExpression(ExpressionStmt $stmt): void
    {
        $this->resolveExpr($stmt->expression);
    }

    protected function handlePrint(PrintStmt $stmt): void
    {
        $this->resolveExpr($stmt->expression);
    }


    protected function handleIf(IfStmt $stmt): void
    {
        $this->resolveExpr($stmt->condition);
        $this->resolveStmt($stmt->thenBranch);

        if ($stmt->elseBranch) {
            $this->resolveStmt($stmt->elseBranch);
        }
    }

    protected function handleWhile(WhileStmt $stmt): void
    {
        $this->resolveExpr($stmt->condition);
        $this->resolveStmt($stmt->body);
    }

    protected function handleBreak(): void
    {
    }

    protected function handlecontinue(): void
    {
    }

    protected function handleReturnStmt(ReturnStmt $stmt): void
    {
        if ($this->currentFunction == FunctionType::NONE) {
            $this->errorHelper->error($stmt->keyword, "Não é possível usar esse comando fora de uma função");
        }

        if ($stmt->value) {
            if ($this->currentFunction == FunctionType::INITIALIZER) {
                $this->errorHelper->error($stmt->keyword, "Não é possível retornar valor de um construtor");
            }

            $this->resolveExpr($stmt->value);
        }
    }

    protected function handleClassDecl(ClassDecl $stmt): void
    {
        $ensclosingClass = $this->currentClass;
        $this->currentClass = ClassType::PHORTCLASS;

        $this->declare($stmt->name->lexeme);
        $this->define($stmt->name->lexeme);

        $this->beginScope();
        $this->scopes->peek()->put(TokenType::THIS->value, true);

        foreach ($stmt->body as $method) {
            /** @var FunctionStmt $method */
            $isInitializer = $method->name->lexeme == TokenType::CONSTRUCTOR->value;
            $this->resolveFunction($method, $isInitializer ? FunctionType::INITIALIZER : FunctionType::METHOD);
        }

        $this->endScope();
        $this->currentClass = $ensclosingClass;
    }

    // Now, expressions

    protected function handleBinary(BinaryExpr $expr): void
    {
        $this->resolveExpr($expr->left);
        $this->resolveExpr($expr->right);
    }

    protected function handleUnary(UnaryExpr $expr): void
    {
        $this->resolveExpr($expr->right);
    }

    protected function handleLiteral(LiteralExpr $expr): void
    {
    }

    protected function handleGrouping(GroupingExpr $expr): void
    {
        $this->resolveExpr($expr->expression);
    }

    protected function handleConditional(ConditionalExpr $expr): void
    {
        $this->resolveExpr($expr->condition);
        $this->resolveExpr($expr->trueExpr);
        $this->resolveExpr($expr->falseExpr);
    }

    protected function handleLogicalExpr(LogicalExpr $expr): void
    {
        $this->resolveExpr($expr->right);
        $this->resolveExpr($expr->left);
    }

    protected function handleCallExpr(CallExpr $expr): void
    {
        $this->resolveExpr($expr->callee);

        foreach ($expr->arguments as $arg) {
            $this->resolveExpr($arg);
        }
    }

    protected function handleLambdaExpr(LambdaExpr $expr): void
    {
        $currentFunction = $this->currentFunction;
        $this->currentFunction = FunctionType::FUNCTION;

        $this->beginScope();

        foreach ($expr->parameters as $param) {
            $this->declare($param->lexeme);
            $this->define($param->lexeme);
        }
        $this->resolve($expr->body);

        $this->endScope();

        $this->currentFunction = $currentFunction;
    }

    protected function handleArrayDefExpr(ArrayDefExpr $expr): void
    {
        foreach ($expr->elements as $element) {
            $this->resolveExpr($element);
        }
    }

    protected function handleArrayGetExpr(ArrayGetExpr $expr): void
    {
        $this->resolveExpr($expr->array);
        $this->resolveExpr($expr->index);
    }

    protected function handleArraySetExpr(ArraySetExpr $expr): void
    {
        $this->resolveExpr($expr->array);
        $this->resolveExpr($expr->index);
        $this->resolveExpr($expr->assignment);
    }

    protected function handleGetExpr(GetExpr $expr): void
    {
        $this->resolveExpr($expr->object);
    }

    protected function handleSetExpr(SetExpr $expr): void
    {
        $this->resolveExpr($expr->value);
        $this->resolveExpr($expr->object);
    }

    protected function handleThisExpr(ThisExpr $expr): void
    {
        if ($this->currentClass == ClassType::NONE) {
            $this->errorHelper->error($expr->keyword, "Não é possível usar o esse operador fora de uma classe");
        }
        $this->resolveLocal($expr, $expr->keyword);
    }
}
