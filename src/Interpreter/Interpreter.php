<?php

namespace Phortugol\Interpreter;

use Phortugol\Exceptions\RuntimeError;
use Phortugol\Expr\Expr;
use Phortugol\Helpers\ErrorHelper;
use Phortugol\Stmt\BlockStmt;
use Phortugol\Stmt\ExpressionStmt;
use Phortugol\Stmt\PrintStmt;
use Phortugol\Stmt\Stmt;
use Phortugol\Stmt\StmtHandler;
use Phortugol\Stmt\VarStmt;

// TODO: Adicionar if/else
// TODO: Implementar short circuit de operadores lógics OR e AND
//
// TODO: Escrever alguns arrquivos de teste (tipo de integração)
// TODO: Desafio: Tentar implementar vc mesmo uma versão de while e for loops
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
}
