<?php

namespace GenericDatabase\Tests\Shared;

use PHPUnit\Framework\TestCase;
use GenericDatabase\Shared\Transporter;

class TransporterTraitTest extends TestCase
{
    /**
     * Create a concrete test class that uses the Transporter trait
     */
    private function createConcreteClass(): string
    {
        $className = 'TestTransporterClass_' . uniqid();

        eval('
            class ' . $className . ' {
                use \GenericDatabase\Shared\Transporter;

                private $field;

                public function setField($value)
                {
                    $this->field = $value;
                    $this->property["field"] = $value;
                }

                public function getField()
                {
                    return $this->field;
                }
            }
        ');

        return $className;
    }

    public function testSleepAndWakeupMethodRestoresProperties(): void
    {
        // Arrange
        $className = $this->createConcreteClass();
        $instance = new $className();
        $instance->setField('value');

        // Act
        $serialized = serialize($instance);
        $unserialized = unserialize($serialized);

        // Assert
        $this->assertEquals('value', $unserialized->getField());
    }
}
