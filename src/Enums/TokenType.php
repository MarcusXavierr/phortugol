<?php

namespace Toyjs\Toyjs\Enums;

enum TokenType: string
{
    // Single character tokens.
    case LEFT_PAREN = '('; case RIGHT_PAREN = ')'; case LEFT_BRACE = '{'; case RIGHT_BRACE = '}';
    case COMMA = ','; case DOT = '.'; case SEMICOLON = ';';

    case SLASH = '/'; case STAR = '*'; case MINUS = '-'; case PLUS = '+';
    case BANG = '!'; case GREATER = '>'; case LESS = '<'; case EQUAL = '='; case MODULO = '%';

    // two character tokens.
    case BANG_EQUAL = '!='; case EQUAL_EQUAL = '=='; case GREATER_EQUAL = '>='; case LESS_EQUAL = '<=';
    case PLUS_PLUS = '++'; case MINUS_MINUS = '--';

    // Literals
    case IDENTIFIER = 'identifier'; case STRING = 'string'; case NUMBER = 'number';

    // Language keywords
    case IF = 'if'; case ELSE = 'else'; case TRUE = 'true'; case FALSE = 'false';
    case AND = '&&'; case OR = '||'; case FOR = 'for'; case WHILE = 'while';
    case BREAK = 'break'; case CONTINUE = 'continue';
    case VAR = 'var'; case LET = 'let'; case CONST = 'const';
    case FUNCTION = 'function'; case RETURN = 'return';
    case NULL = 'null'; case UNDEFINED = 'undefined';

    case EOF = '\0';
}
