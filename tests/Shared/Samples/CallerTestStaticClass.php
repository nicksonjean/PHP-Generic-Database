<?php

namespace GenericDatabase\Tests\Shared\Samples;

use GenericDatabase\Shared\Caller as Call;

class CallerTestStaticClass
{
    use Call;

    public static function test($name, $arguments)
    {
        return $name . ' ' . implode(' ', $arguments);
    }
}
