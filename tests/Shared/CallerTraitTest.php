<?php

namespace GenericDatabase\Tests\Shared;

use PHPUnit\Framework\TestCase;
use GenericDatabase\Tests\Shared\Samples\CallerTestClass;
use GenericDatabase\Tests\Shared\Samples\CallerTestStaticClass;

class CallerTraitTest extends TestCase
{
    private $objectUsingCaller;
    private $objectUsingCalling;

    protected function setUp(): void
    {
        $this->objectUsingCaller = new CallerTestClass();
        $this->objectUsingCalling = new CallerTestStaticClass();
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

    public function testCallStaticWithCallMethod()
    {
        $result = $this->objectUsingCaller->__callStatic('call', ['arg1', 'arg2']);
        $this->assertInstanceOf(get_class($this->objectUsingCaller), $result);
    }

    public function testCallStaticWithNonExistingCallMethod()
    {
        $result = $this->objectUsingCalling::__callStatic('nonExistentMethod', [null]);
        $this->assertEquals(null, $result);
    }

    public function testCallStaticWithNonExistingMethod()
    {
        $result = $this->objectUsingCalling::__callStatic('nonExistingMethod', [null]);
        $this->assertNull($result);
    }

    public function testCallStaticReturnsNullForNonExistentMethod()
    {
        $result = $this->objectUsingCalling::__callStatic('nonExistentMethod', []);
        $this->assertNull($result);
    }
}
