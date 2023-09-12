<?php

namespace GenericDatabase\Tests\Helpers;

use GenericDatabase\Helpers\Reflections;
use GenericDatabase\Tests\Helpers\Samples\MyClass;
use GenericDatabase\Tests\Helpers\Samples\MyClassNonInstance;
use PHPUnit\Framework\TestCase;

final class ReflectionsTest extends TestCase
{
    private const REFCLASS = 'GenericDatabase\Helpers\Reflections';

    public function testGetSingletonInstanceValid()
    {
        $instance = Reflections::getSingletonInstance(MyClass::class);
        $this->assertInstanceOf(MyClass::class, $instance);
    }

    public function testGetSingletonInstanceWithNonExistentMethod()
    {
        $result = Reflections::getSingletonInstance(MyClassNonInstance::class);
        $this->assertFalse($result);
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

    public function testCreateObjectAndSetPropertiesCaseInsensitiveWithObject()
    {
        $object = new \stdClass();
        $constructorArgs = ['arg1', 'arg2'];
        $propertyList = ['Name' => 'John', 'Age' => 30];

        $result = Reflections::createObjectAndSetPropertiesCaseInsensitive($object, $constructorArgs, $propertyList);

        $this->assertSame($object, $result);
        $this->assertEquals('John', $result->Name);
        $this->assertEquals(30, $result->Age);
    }

    public function testCreateObjectWithObject()
    {
        $object = new \stdClass();
        $constructorArgs = ['arg1', 'arg2'];

        $method = new \ReflectionMethod(self::REFCLASS, 'createObject');
        $method->setAccessible(true); // NOSONAR
        $result = $method->invokeArgs(null, [$object, $constructorArgs]);

        $this->assertSame($object, $result);
    }

    public function testCreateObjectWithClassName()
    {
        $className = MyClassNonInstance::class;
        $constructorArgs = ['name'];

        $method = new \ReflectionMethod(self::REFCLASS, 'createObject');
        $method->setAccessible(true); // NOSONAR
        $result = $method->invokeArgs(null, [$className, $constructorArgs]);

        $this->assertInstanceOf($className, $result);
    }

    public function testSetPropertiesCaseInsensitive()
    {
        $object = new MyClassNonInstance();
        $propertyList = [
            'name' => 'John',
            'Age' => 30,
            'cIty' => 'New York',
        ];

        $method = new \ReflectionMethod(self::REFCLASS, 'setPropertiesCaseInsensitive');
        $method->setAccessible(true); // NOSONAR
        $method->invokeArgs(null, [$object, $propertyList]);

        $this->assertEquals('John', $object->name);
        $this->assertEquals(30, $object->age);
        $this->assertEquals('New York', $object->city);
    }

    public function testSetPropertiesCaseInsensitiveWithExistingProperties()
    {
        $object = new MyClassNonInstance();
        $object->name = 'Alice';
        $propertyList = [
            'Name' => 'Bob',
            'age' => 25,
            'Live' => 'yes'
        ];

        $method = new \ReflectionMethod(self::REFCLASS, 'setPropertiesCaseInsensitive');
        $method->setAccessible(true); // NOSONAR
        $method->invokeArgs(null, [$object, $propertyList]);

        $this->assertEquals('Bob', $object->name);
        $this->assertEquals(25, $object->age);
    }

    public function testArgsToArray()
    {
        $arg1 = ['key1' => 'value1'];
        $arg2 = ['key2' => 'value2'];
        $result = Reflections::argsToArray($arg1, $arg2);
        $this->assertEquals(['key1' => 'value1', 'key2' => 'value2'], $result);
    }
}
