<?php

namespace Phortugol\Interpreter;

use Ds\Map;
use Phortugol\Exceptions\BreakException;
use Phortugol\Exceptions\ContinueException;
use Phortugol\Exceptions\ReturnException;
use Phortugol\Exceptions\RuntimeError;
use Phortugol\Expr\Expr;
use Phortugol\Helpers\ErrorHelper;
use Phortugol\NativeFunctions\ArraySize;
use Phortugol\NativeFunctions\ArrayPush;
use Phortugol\NativeFunctions\Clock;
use Phortugol\NativeFunctions\PhortugolFunction;
use Phortugol\NativeFunctions\Pow;
use Phortugol\NativeFunctions\Read;
use Phortugol\Stmt\BlockStmt;
use Phortugol\Stmt\ExpressionStmt;
use Phortugol\Stmt\FunctionStmt;
use Phortugol\Stmt\IfStmt;
use Phortugol\Stmt\PrintStmt;
use Phortugol\Stmt\ReturnStmt;
use Phortugol\Stmt\Stmt;
use Phortugol\Stmt\StmtHandler;
use Phortugol\Stmt\VarStmt;
use Phortugol\Stmt\WhileStmt;

class Interpreter
{
    /** @use StmtHandler<void> */
    use StmtHandler;

    private readonly ErrorHelper $errorHelper;
    private readonly TypeValidator $typeValidator;
    private readonly ExprInterpreter $exprInterpreter;
    public readonly Environment $globals;
    public Map $locals;
    public Environment $environment;

    public function __construct(ErrorHelper $errorHelper)
    {
        $this->errorHelper = $errorHelper;
        $this->typeValidator = new TypeValidator();
        $this->globals = new Environment(null);
        $this->environment = $this->globals;
        $this->exprInterpreter = new ExprInterpreter($this->errorHelper, $this->environment, $this);
        $this->locals = new Map();
        $this->mountNativeFunctions();
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

    public function evaluate(Expr $expression): mixed
    {
        return $this->exprInterpreter->evaluate($expression);
    }

    public function resolve(Expr $expr, int $depth): void
    {
        $this->locals->put($expr, $depth);
    }

    // Statements
    protected function handlePrint(PrintStmt $stmt): void
    {
        $result = $this->evaluate($stmt->expression);

        if ($result === true) {
            echo "verdadeiro";
        } else if ($result === false) {
            echo "falso";
        } else if ($result instanceof Map) {
            printArray($result);
        }
        else {
            echo $result;
        }

        // TODO: Conseguir remover isso e fazer o "\n" ser interpretado como fim de linha
        echo PHP_EOL;
    }

    protected function handleExpression(ExpressionStmt $stmt): void
    {
        $this->evaluate($stmt->expression);
    }

    protected function handleVarStmt(VarStmt $stmt): void
    {
        $initializer = null;
        if ($stmt->initializer !== null) {
            $initializer = $this->evaluate($stmt->initializer);
        }

        $this->environment->define($stmt->identifier, $initializer);
    }

    protected function handleBlockStmt(BlockStmt $stmt): void
    {
        $this->executeBlock($stmt->declarations, new Environment($this->environment));
    }

    protected function handleIf(IfStmt $stmt): void
    {
        $condition = $this->evaluate($stmt->condition);
        if ($condition) {
            $this->execute($stmt->thenBranch);
        } else if ($stmt->elseBranch) {
            $this->execute($stmt->elseBranch);
        }
    }

    protected function handleWhile(WhileStmt $stmt): void
    {
        try {
            while ($this->evaluate($stmt->condition)) {
                try {
                    $this->execute($stmt->body);
                } catch (ContinueException $e) {
                    // INFO: continues the loop
                    if ($stmt->fallbackIncrement) {
                        $this->execute($stmt->fallbackIncrement);
                    }
                }
            }
        } catch (BreakException $e) {
            // INFO: breaks the loop
        }
    }

    protected function handleBreak(): void
    {
        throw new BreakException();
    }

    protected function handleContinue(): void
    {
        throw new ContinueException();
    }

    protected function handleFunctionStmt(FunctionStmt $stmt): void
    {
        $function = new PhortugolFunction($stmt, $this->environment);
        $this->environment->define($stmt->name->lexeme, $function);
    }

    protected function handleReturnStmt(ReturnStmt $stmt): void
    {
        $value = null;
        if ($stmt->value) {
            $value = $this->exprInterpreter->evaluate($stmt->value);
        }

        throw new ReturnException($value);
    }

    /**
     * @param Stmt[] $statements
     */
    public function executeBlock(array $statements, Environment $block): void
    {
        $previous = $this->environment;

        try {
            $this->environment = $block;
            $this->exprInterpreter->setEnvironment($block);
            foreach($statements as $stmt) {
                $this->execute($stmt);
            }
        } finally {
            // INFO: restore environment
            $this->environment = $previous;
            $this->exprInterpreter->setEnvironment($previous);
        }

    }

    private function mountNativeFunctions(): void
    {
        $this->globals->define("relógio", new Clock());
        $this->globals->define("relogio", new Clock());
        $this->globals->define("potência", new Pow());
        $this->globals->define("potencia", new Pow());
        $this->globals->define("leia", new Read());
        $this->globals->define("tamanho", new ArraySize());
        $this->globals->define("inserir", new ArrayPush());
    }
}

function printArray(Map $arr): void
{
    echo "[";
    for ($i = 0; $i < $arr->count(); $i++) {
        if ($i > 0) {
            echo ", ";
        }
        echo $arr[$i];
    }
    echo "]";
}
