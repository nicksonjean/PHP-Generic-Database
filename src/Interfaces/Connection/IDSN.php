<?php

namespace GenericDatabase\Interfaces\Connection;

use GenericDatabase\Helpers\Exceptions;

interface IDSN
{
    public function parse(): string|Exceptions;
}
