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

    public function testCreateObjectWithReflectionObject()
    {
        $classOrObject = new \stdClass();
        $constructorArgArray = [];

        $method = new \ReflectionMethod(self::REFCLASS, 'createObject');
        $method->setAccessible(true); // NOSONAR
        $result = $method->invokeArgs(null, [$classOrObject, $constructorArgArray]);

        $this->assertInstanceOf('ReflectionObject', $result);
    }

    public function testCreateObjectWithNull()
    {
        $constructorArgArray = [];

        $method = new \ReflectionMethod(self::REFCLASS, 'createObject');
        $method->setAccessible(true); // NOSONAR
        $result = $method->invokeArgs(null, [null, $constructorArgArray]);

        $this->assertInstanceOf(\stdClass::class, $result);
    }

    public function testCreateObjectWithNewInstance()
    {
        $classOrObject = 'GenericDatabase\Tests\Helpers\Samples\MyClass';
        $constructorArgArray = [];

        $method = new \ReflectionMethod(self::REFCLASS, 'createObject');
        $method->setAccessible(true); // NOSONAR
        $result = $method->invokeArgs(null, [$classOrObject, $constructorArgArray]);

        $this->assertInstanceOf(MyClass::class, $result);
    }

    public function testCreateObjectWithObject()
    {
        $constructorArgs = ['name' => 'John', 'age' => 25];
        $propertyList = ['name' => 'Doe', 'age' => 30];

        $object = Reflections::createObjectAndSetPropertiesCaseInsensitive(
            MyClassNonInstance::class,
            $constructorArgs,
            $propertyList
        );

        $this->assertInstanceOf(MyClassNonInstance::class, $object);
        $this->assertEquals('Doe', $object->name);
        $this->assertEquals(30, $object->age);
    }

    public function testCreateObjectWithoutConstructor()
    {
        $method = new \ReflectionMethod(self::REFCLASS, 'createObject');
        $method->setAccessible(true); // NOSONAR
        $object = $method->invokeArgs(null, [MyClass::class, []]);

        $this->assertInstanceOf(MyClass::class, $object);
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
