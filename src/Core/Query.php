<?php

namespace GenericDatabase\Core;

enum Query: int
{
    case RAW = 0;
    case PREPARED = 1;
}
