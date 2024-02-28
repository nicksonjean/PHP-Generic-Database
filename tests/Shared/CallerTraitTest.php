<?php

use PHPUnit\Framework\TestCase;
use GenericDatabase\Shared\Caller;

class CallerTraitTest extends TestCase
{
    private $objectUsingCaller;

    protected function setUp(): void
    {
        $this->objectUsingCaller = new class
        {
            use Caller;
            private $attributes = [];

            public function __set($name, $value)
            {
                $this->attributes[$name] = $value;
            }

            public function __get($name)
            {
                return $this->attributes[$name] ?? null;
            }
        };
    }

    public function testCallSetMethod()
    {
        $this->objectUsingCaller->setName('John Doe');
        $this->assertSame('John Doe', $this->objectUsingCaller->getName());
    }

    public function testCallGetMethod()
    {
        $this->objectUsingCaller->setName('Jane Doe');
        $this->assertSame('Jane Doe', $this->objectUsingCaller->getName());
    }

    public function testCallInaccessibleMethod()
    {
        $this->assertNull($this->objectUsingCaller->nonExistingMethod());
    }

    // public function testCallStaticWithExistingMethod()
    // {
    //     $objectUsingCaller = new class()
    //     {
    //         use Caller;

    //         public static function setName(string $name)
    //         {
    //             return $name;
    //         }
    //     };

    //     $result = $objectUsingCaller::__callStatic('setName', [null]);
    //     $this->assertEquals(null, $result);
    // }

    // public function testCallStaticWithCallMethodAndNonExistsMethod()
    // {
    //     $obj = new class
    //     {
    //         use Caller;
    //         public function call($name, $arguments)
    //         {
    //             return $name . ' ' . implode(' ', $arguments);
    //         }
    //     };

    //     $result = $obj->__callStatic('someMethod', ['arg1', 'arg2']);
    //     $this->assertInstanceOf(get_class($obj), $result);
    // }
}
