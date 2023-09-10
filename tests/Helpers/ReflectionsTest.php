<?php

namespace GenericDatabase\Tests\Helpers;

use GenericDatabase\Helpers\Reflections;
use GenericDatabase\Tests\Helpers\Reflections\MyClass;
use PHPUnit\Framework\TestCase;

final class ReflectionsTest extends TestCase
{
    public function testGetSingletonInstanceValid()
    {
        $instance = Reflections::getSingletonInstance(MyClass::class);
        $this->assertInstanceOf(MyClass::class, $instance);
    }

    public function testIsSingletonMethodExistsValid()
    {
        $result = Reflections::isSingletonMethodExists(MyClass::class);
        $this->assertTrue($result);
    }

    public function testGetClassConstants()
    {
        $constants = Reflections::getClassConstants(MyClass::class);
        $this->assertNotEmpty($constants);
    }

    public function testGetClassConstantName()
    {
        $constantName = Reflections::getClassConstantName(MyClass::class, MyClass::MY_CONSTANT);
        $this->assertEquals('MY_CONSTANT', $constantName);
    }

    public function testGetClassPropertyName()
    {
        $instance = new MyClass();
        $value = Reflections::getClassPropertyName($instance, 'myProperty');
        $this->assertEquals('myPropertyValue', $value);
    }

    public function testArgsToArray()
    {
        $arg1 = ['key1' => 'value1'];
        $arg2 = ['key2' => 'value2'];
        $result = Reflections::argsToArray($arg1, $arg2);
        $this->assertEquals(['key1' => 'value1', 'key2' => 'value2'], $result);
    }
}
