<?php

/** @noinspection ALL */

namespace GenericDatabase\Core;

enum Join: string
{
    case DEFAULT = 'DEFAULT';
    case SELF = 'SELF';
    case LEFT = 'LEFT';
    case RIGHT = 'RIGHT';
    case INNER = 'INNER';
    case OUTER = 'OUTER';
    case CROSS = 'CROSS';
}
