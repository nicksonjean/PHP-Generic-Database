<?php

namespace GenericDatabase\Tests\Helpers\Samples;

class MyClassNonInstance
{
    public $name;
    public $age;
    public $city;

    final public function __construct()
    {
        // Do something nothing
    }
}
