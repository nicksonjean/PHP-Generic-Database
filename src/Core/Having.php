<?php

namespace GenericDatabase\Core;

enum Having: string
{
    case DEFAULT = 'DEFAULT';
    case NONE = 'NONE';
    case FUNCTION = 'FUNCTION';
    case NEGATION = 'NEGATION';
    case AFFIRMATION = 'AFFIRMATION';
    case BETWEEN = 'BETWEEN';
    case IN = 'IN';
    case LIKE = 'LIKE';
    case EXISTS = 'EXISTS';
    case ISNULL = 'ISNULL';
    case IFNULL = 'IFNULL';
}