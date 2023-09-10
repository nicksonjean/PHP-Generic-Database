<?php

namespace GenericDatabase\Tests\Reflections;

class MyClass
{
    const MY_CONSTANT = 'myConstantValue';
    public static $myProperty = 'myPropertyValue';

    public static function getInstance()
    {
        return new self();
    }
}
