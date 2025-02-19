<?php

namespace GenericDatabase\Engine\Firebird\Connection;

use GenericDatabase\Helpers\Exceptions;
use AllowDynamicProperties;

#[AllowDynamicProperties]
interface IDSN
{
    public static function parse(): string|Exceptions;
}
