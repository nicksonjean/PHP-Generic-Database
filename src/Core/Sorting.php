<?php

/** @noinspection ALL */

namespace GenericDatabase\Core;

enum Sorting: string
{
    case NONE = 'NONE';
    case ASCENDING = 'ASCENDING';
    case DESCENDING = 'DESCENDING';
    case METADATA = 'METADATA';
    case FUNCTION = 'FUNCTION';
}
