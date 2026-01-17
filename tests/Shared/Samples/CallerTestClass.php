<?php

namespace GenericDatabase\Tests\Shared\Samples;

use GenericDatabase\Shared\Caller as Call;

class CallerTestClass
{
    use Call;

    private $attributes = [];

    public function __set($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    public function __get($name)
    {
        return $this->attributes[$name] ?? null;
    }

    public function call($name, $arguments)
    {
        return $name . ' ' . implode(' ', $arguments);
    }
}
