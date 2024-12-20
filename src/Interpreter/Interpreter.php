<?php

namespace Phortugol\Interpreter;

use Ds\Map;
use Phortugol\Enums\TokenType;
use Phortugol\Exceptions\BreakException;
use Phortugol\Exceptions\ContinueException;
use Phortugol\Exceptions\ReturnException;
use Phortugol\Exceptions\RuntimeError;
use Phortugol\Expr\Expr;
use Phortugol\Helpers\ErrorHelper;
use Phortugol\NativeFunctions\ArrayInsert;
use Phortugol\NativeFunctions\ArraySize;
use Phortugol\NativeFunctions\ArrayPush;
use Phortugol\NativeFunctions\Clock;
use Phortugol\NativeFunctions\FillArray;
use Phortugol\NativeFunctions\KeyExists;
use Phortugol\NativeFunctions\NewVector;
use Phortugol\NativeFunctions\PhortClass;
use Phortugol\NativeFunctions\PhortugolFunction;
use Phortugol\NativeFunctions\Pow;
use Phortugol\NativeFunctions\Random;
use Phortugol\NativeFunctions\Read;
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
use Stringable;

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
        else if ($result instanceof \SplFixedArray) {
            printArray($result);
        }
        else if (gettype($result) === "string") {
            $result = str_replace('\n', "\n", $result);
            echo $result;
        } else {
            echo $result;
        }
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

    protected function handleClassDecl(ClassDecl $stmt): void
    {
        $this->environment->define($stmt->name->lexeme, null);

        $methods = new Map();
        // TODO: Quando eu conseguir parsear atributos fora de métodos, nós vamos mudar um pouco essa lógica. Talvez adicionar uma validação de tipo
        foreach ($stmt->body as $method) {
            if ($method instanceof FunctionStmt) {
                $isInit = $method->name->lexeme == TokenType::CONSTRUCTOR->value;

                $function = new PhortugolFunction($method, $this->environment, $isInit);
                $methods->put($method->name->lexeme, $function);
            }

            throw new RuntimeError($stmt->name, "Encontrado problema na classe, têm uma estrutura que não é um método dentro dela");
        }

        $phortClass = new PhortClass($stmt->name->lexeme, $methods);
        $this->environment->assign($stmt->name, $phortClass);
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
        $this->globals->define("empilhar", new ArrayPush());
        $this->globals->define("inserir", new ArrayInsert());
        $this->globals->define("temChave", new KeyExists());
        $this->globals->define("intAleatório", new Random());
        $this->globals->define("intAleatorio", new Random());
        $this->globals->define("preencherLista", new FillArray());
        $this->globals->define("vetor", new NewVector());
    }
}

// TODO: Refactor later
function printArray(\SplFixedArray|Map $arr): void
{
    $isAssoc = false;
    $i = 0;
    foreach ($arr as $key => $value) {
        if ($key != $i) {
            $isAssoc = true;
            break;
        }
        $i++;
    }

    echo "[";

    if ($isAssoc) {
        $count = 0;
        foreach ($arr as $key => $value) {
            $text = "";
            if ($count > 0) {
                $text .= ", ";
            }

            $count++;
            echo $text . $key . " => " . $value;
        }
    } else {
        $count = 0;
        foreach ($arr as $value) {
            if ($count > 0) {
                echo ", ";
            }
            $count++;
            echo $value;
        }
    }

    echo "]";
}
