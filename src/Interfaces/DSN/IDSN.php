<?php

namespace GenericDatabase\Interfaces\DSN;

use GenericDatabase\Helpers\Exceptions;

interface IDSN
{
    public function parse(): string|Exceptions;
}
