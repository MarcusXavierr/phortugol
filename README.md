# Phortugol, um simples interpretador de portugol escrito em PHP

Este projeto tem como objetivo implementar uma versão de portugol semelhante ao JavaScript.

Para executar o interpretador, você precisa ter PHP 8.2 ou superior instalado no seu sistema, e também o Composer.

Para instalar as dependências (nenhuma até agora), execute:

```bash
composer install
```
Para executar o interpretador, rode:

```bash
./phortugol <filename>
```

ou simplesmente execute o interpretador sem nenhum argumento para entrar no modo REPL:
```bash
./phortugol
```

Para rodar os testes (boa parte do parser e scanner estão cobertos com testes) basta rodar
```
composer test
```

## Roadmap
- [x] Adicionar escopo nos blocos da linguagem
- [x] Implementar suporte a loops e condicionais
- [x] Implementar funções
- [x] Criar um mecanismo para cadastrar funções nativas na linguagem
- [x] Suportar closures
- [x] Melhorar o escopo estático
- [x] Implementar suporte a arrays
- [ ] Implementar classes
- [ ] Criar uma página para documentar a sintaxe da linguagem
- [ ] Publicar a extensão para VS Code que adiciona syntax highlight em arquivos `.port`
- [ ] Implementar alguma função para tornar o programa capaz de ler input do usuário

## Roadmap fase 2
- [ ] Criar uma máquina virtual que roda bytecode para phortugol
- [ ] Criar um compilador de Phortugol para o bytecode dessa máquina virtual
- [ ] Implementar IO de arquivos na biblioteca padrão
- [ ] Mapear outras funções interessantes para serem implementadas na stdlib da linguagem


## Notação da "gramática" de phortugol
Ainda não está totalmente finalizada, e talvez no futuro eu migre pra uma notação EBNF. A tendência é as regras com precedência mais alta ficarem mais no fundo do arquivo. Começamos com statements, e depois vamos pra expressions.

```
program -> declaration* EOF;

// stmts
declaration -> funcDecl | varDecl | statement;
funcDecl -> ("função" | "funcao" ) function;
function -> IDENTIFIER "(" parameters? ")" block;
parameters -> IDENTIFIER ( "," IDENTIFIER)*;

varDecl -> "var" IDENTIFIER ("=" expression)? ";"

statement ->  exprStmt
			| printStmt
			| block
			| ifStmt
			| whileStmt
			| forStmt
			| returnStmt

exprStmt -> expression ";" ;
printStmt -> "escreva" expression ";" ;
block -> "{" declaration* "}";
ifStmt -> "se" "(" expression ")" statement ("senao" statement)? ;
whileStmt -> "enquanto" "(" expression ")" statement;
forStmt -> "repetir" "("
			(varDecl | exprStmt)? ";"
			expression? ";"
			expression? ")" statement;
returnStmt -> "retorne" expression? ";" ;

// Expressions
expression     → lambda ;
lambda        -> "(" parameters? ")" "=>" (block | expression)
                 | assignment;

assignment -> IDENTIFIER "=" assignment
			 | conditional;

conditional   -> logic_or ("?" expression ":" conditional)?;
logic_or       → logic_and ( "OU" logic_and )* ;
logic_and      → equality ( "E" equality )* ;
equality       → comparison ( ( "!=" | "==" ) comparison )* ;
comparison     → term ( ( ">" | ">=" | "<" | "<=" ) term )* ;
term           → factor ( ( "-" | "+" ) factor )* ;
factor         → unary ( ( "/" | "*" | "%" ) unary )* ;
unary          → ( "!" | "-" ) unary
               | call ;
call           -> arrayGet ( "(" arguments? ")" )*;
arrayGet       -> arrayDef ( "[" (expression) "]" )*
arrayDef      -> "[" (expression)? ("," expression)* "]";
               | primary;
primary        → NUMBER | STRING | "true" | "false" | "nil"
               | "(" expression ")" ;
arguments      -> expression ("," expression)*;
```
