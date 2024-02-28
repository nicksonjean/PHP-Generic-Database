<?php

namespace GenericDatabase\Tests\Shared;

use PHPUnit\Framework\TestCase;
use GenericDatabase\Shared\Transporter;

class TransporterTraitTest extends TestCase
{
    public function testSleepAndWakeupMethodRestoresProperties()
    {
        $mock = $this->getMockForTrait(Transporter::class);
        $mock->property = ['field' => 'value']; // @phpstan-ignore-line

        $serialized = serialize($mock);
        $unserialized = unserialize($serialized);

        $this->assertEquals('value', $unserialized->field);
    }
}
