<?php

namespace GenericDatabase\Tests\Helpers;

use GenericDatabase\Helpers\CustomException;
use GenericDatabase\Helpers\Errors;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \GenericDatabase\Helpers\Errors
 */
final class ErrorsTest extends TestCase
{
    public function testTurnOn()
    {
        $previousSetting = Errors::turnOn();
        $this->assertEquals(1, $previousSetting);
    }

    public function testTurnOff()
    {
        $previousSetting = Errors::turnOff();
        $this->assertEquals(1, $previousSetting);
    }

    public function testThrowWithException()
    {
        $exception = new CustomException("Test Exception");
        $result = Errors::throw($exception);
        $expected = json_encode([
            'message' => $exception->getMessage(),
            'location' => "{$exception->getFile()}:{$exception->getLine()}"
        ]);

        $this->assertEquals($expected, $result);
    }
}
