<?php

namespace Phortugol\Interpreter;

use Phortugol\Exceptions\RuntimeError;
use Phortugol\Expr\Expr;
use Phortugol\Helpers\ErrorHelper;
use Phortugol\Stmt\BlockStmt;
use Phortugol\Stmt\ExpressionStmt;
use Phortugol\Stmt\IfStmt;
use Phortugol\Stmt\PrintStmt;
use Phortugol\Stmt\Stmt;
use Phortugol\Stmt\StmtHandler;
use Phortugol\Stmt\VarStmt;
use Phortugol\Stmt\WhileStmt;

// TODO: Escrever alguns arrquivos de teste (tipo de integração)
// TODO: Adicionar a opção de ter lexemas UTF-8. E adicionar senão como um token de ELSE válido
// TODO: Implementar NULLs
class Interpreter
{
    /** @use StmtHandler<void> */
    use StmtHandler;

    private readonly ErrorHelper $errorHelper;
    private readonly TypeValidator $typeValidator;
    private readonly ExprInterpreter $exprInterpreter;
    private Environment $environment;

    public function __construct(ErrorHelper $errorHelper)
    {
        $this->errorHelper = $errorHelper;
        $this->typeValidator = new TypeValidator();
        $this->environment = new Environment(null);
        $this->exprInterpreter = new ExprInterpreter($this->errorHelper, $this->environment);
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

    private function evaluate(Expr $expression): mixed
    {
        return $this->exprInterpreter->evaluate($expression);
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
        $previous = $this->environment;
        $block = new Environment($this->environment);
        try {
            $this->environment = $block;
            $this->exprInterpreter->setEnvironment($block);
            foreach($stmt->declarations as $declaration) {
                $this->execute($declaration);
            }
        } finally {
            // INFO: restore environment
            $this->environment = $previous;
            $this->exprInterpreter->setEnvironment($previous);
        }
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
        while ($this->evaluate($stmt->condition)) {
            $this->execute($stmt->body);
        }
    }
}
