<?php

namespace GenericDatabase\Tests\Helpers\Samples;

class MyClass
{
    const MY_CONSTANT = 'myConstantValue';
    public static $myProperty = 'myPropertyValue';

    public static function getInstance()
    {
        return new self();
    }
}