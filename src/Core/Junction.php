<?php

/** @noinspection ALL */

namespace GenericDatabase\Core;

enum Junction: string
{
    case NONE = 'NONE';
    case CONJUNCTION = 'CONJUNCTION';
    case DISJUNCTION = 'DISJUNCTION';
}
