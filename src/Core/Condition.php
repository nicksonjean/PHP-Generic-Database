<?php

/** @noinspection ALL */

namespace GenericDatabase\Core;

enum Condition: string
{
    case NONE = 'NONE';
    case CONJUNCTION = 'CONJUNCTION';
    case DISJUNCTION = 'DISJUNCTION';
}
