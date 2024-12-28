<?php

/** @noinspection ALL */

namespace GenericDatabase\Core;

enum Grouping: string
{
    case DEFAULT = 'DEFAULT';
    case COMPOUND = 'COMPOUND';
    case METADATA = 'METADATA';
    case FUNCTION = 'FUNCTION';
}
