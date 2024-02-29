<?php

namespace GenericDatabase\Tests\Shared;

use PHPUnit\Framework\TestCase;
use GenericDatabase\Tests\Shared\Samples\SingletonStub;

class SingletonTraitTest extends TestCase
{
    public function testThatSingletonReturnsSameInstance(): void
    {
        $firstInstance = SingletonStub::getInstance();
        $secondInstance = SingletonStub::getInstance();

        $this->assertSame($firstInstance, $secondInstance);
    }

    public function testThatSingletonReturnsANewInstanceWhenCleared(): void
    {
        $firstInstance = SingletonStub::getInstance();
        SingletonStub::clearInstance();
        $secondInstance = SingletonStub::getInstance();

        $this->assertNotSame($firstInstance, $secondInstance);
    }

    public function testThatSingletonCanSetAnInstance(): void
    {
        $instance = new SingletonStub();
        SingletonStub::setInstance($instance);

        $this->assertSame($instance, SingletonStub::getInstance());
    }
}
