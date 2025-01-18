<?php

namespace GenericDatabase\Tests\Shared;

use PHPUnit\Framework\TestCase;
use GenericDatabase\Tests\Shared\Samples\CleanerMock;

class CleanerTraitTest extends TestCase
{
    private $cleanerMock;

    protected function setUp(): void
    {
        $this->cleanerMock = new CleanerMock();
    }

    public function testPropertyIsSet()
    {
        $name = 'testProperty';

        $reflection = new \ReflectionClass($this->cleanerMock);
        $property = $reflection->getProperty('property');
        $property->setAccessible(true);

        $property->setValue($this->cleanerMock, [$name => 'value']);

        $this->assertTrue(isset($this->cleanerMock->{$name}));
    }

    public function testPropertyIsNotSet()
    {
        $name = 'testProperty';
        $this->assertFalse(isset($this->cleanerMock->{$name}));
    }

    public function testUnset()
    {
        $name = 'testProperty';

        $reflection = new \ReflectionClass($this->cleanerMock);
        $property = $reflection->getProperty('property');
        $property->setAccessible(true);

        unset($this->cleanerMock->{$name});

        $this->assertFalse(isset($this->cleanerMock->{$name}));
    }
}
