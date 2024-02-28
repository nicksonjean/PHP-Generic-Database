<?php

namespace GenericDatabase\Tests\Shared;

use PHPUnit\Framework\TestCase;
use GenericDatabase\Shared\Cleaner;

class CleanerTraitTest extends TestCase
{
    private $cleanerMock;

    protected function setUp(): void
    {
        $traitName = Cleaner::class;

        $this->cleanerMock = $this->getMockBuilder($traitName)->getMockForTrait();
    }

    public function testPropertyIsSet()
    {
        $name = 'testProperty';

        $this->cleanerMock->property = [$name => 'value'];

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

        $this->cleanerMock->property = [$name => 'value'];

        unset($this->cleanerMock->{$name});

        $this->assertFalse(isset($this->cleanerMock->{$name}));
    }
}
