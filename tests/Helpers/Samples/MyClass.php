<?php

namespace GenericDatabase\Tests\Helpers\Samples;

class MyClass
{
    public const MY_CONSTANT = 'myConstantValue';
    public static $myProperty = 'myPropertyValue';

    public static function getInstance()
    {
        return new self();
    }
}
