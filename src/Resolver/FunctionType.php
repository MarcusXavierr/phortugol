<?php

namespace Phortugol\Resolver;

enum FunctionType
{
    case NONE;
    case FUNCTION;
    case INITIALIZER;
    case METHOD;
}
