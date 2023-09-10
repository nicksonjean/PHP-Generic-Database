<?php

namespace GenericDatabase\Tests;

use GenericDatabase\Helpers\Reflections;
use PHPUnit\Framework\TestCase;

class MyClass
{
    const MY_CONSTANT = 'myConstantValue';
    public static $myProperty = 'myPropertyValue';

    public static function getInstance()
    {
        return new self();
    }
}

class InvalidClass
{
    // Esta classe não possui o método getInstance
}

final class ReflectionsTest extends TestCase
{
    public function testGetSingletonInstanceValid()
    {
        // Teste com uma classe válida
        $instance = Reflections::getSingletonInstance(MyClass::class);
        $this->assertInstanceOf(MyClass::class, $instance);
    }

    public function testIsSingletonMethodExistsValid()
    {
        // Teste com uma classe válida que possui o método estático esperado
        $result = Reflections::isSingletonMethodExists(MyClass::class);
        $this->assertTrue($result);
    }

    public function testGetClassConstants()
    {
        // Teste para obter as constantes de uma classe
        $constants = Reflections::getClassConstants(MyClass::class);
        $this->assertNotEmpty($constants);
    }

    public function testGetClassConstantName()
    {
        // Teste para obter o nome de uma constante por seu valor
        $constantName = Reflections::getClassConstantName(MyClass::class, MyClass::MY_CONSTANT);
        $this->assertEquals('MY_CONSTANT', $constantName);
    }

    public function testGetClassPropertyName()
    {
        // Teste para obter o valor de uma propriedade de classe por seu nome
        $instance = new MyClass();
        $value = Reflections::getClassPropertyName($instance, 'myProperty');
        $this->assertEquals('myPropertyValue', $value);
    }

    public function testArgsToArray()
    {
        // Teste para converter argumentos em um array associativo
        $arg1 = ['key1' => 'value1'];
        $arg2 = ['key2' => 'value2'];
        $result = Reflections::argsToArray($arg1, $arg2);
        $this->assertEquals(['key1' => 'value1', 'key2' => 'value2'], $result);
    }
}
