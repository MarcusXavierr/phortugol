<?php

namespace Toyjs\Toyjs\Enums;

enum TokenType: string
{
    // Single character tokens.
    case LEFT_PAREN = '('; case RIGHT_PAREN = ')'; case LEFT_BRACE = '{'; case RIGHT_BRACE = '}';
    case COMMA = ','; case DOT = '.'; case SEMICOLON = ';';

    case SLASH = '/'; case STAR = '*'; case MINUS = '-'; case PLUS = '+';
    case BANG = '!'; case GREATER = '>'; case LESS = '<'; case EQUAL = '='; case MODULO = '%';

    case QUESTION = '?'; case COLON = ':';

    // two character tokens.
    case BANG_EQUAL = '!='; case EQUAL_EQUAL = '=='; case GREATER_EQUAL = '>='; case LESS_EQUAL = '<=';
    case PLUS_PLUS = '++'; case MINUS_MINUS = '--';

    // Literals
    case IDENTIFIER = 'identifier'; case STRING = 'string'; case NUMBER = 'number';

    // Language keywords
    case IF = 'se'; case ELSE = 'senao'; case TRUE = 'verdadeiro'; case FALSE = 'falso';
    case AND = '&&'; case OR = '||'; case FOR = 'para'; case WHILE = 'enquanto';
    case BREAK = 'pare'; case CONTINUE = 'continue';
    case VAR = 'var';
    case FUNCTION = 'funcao'; case RETURN = 'retorne';
    case NULL = 'nulo'; case UNDEFINED = 'indefinido';

    case PRINT = 'escreva';
    case EOF = '\0';
}
