<?php

namespace GenericDatabase\Tests\Helpers;

use Exception;
use Throwable;
use GenericDatabase\Helpers\CustomException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \GenericDatabase\Helpers\CustomException
 */
final class CustomExceptionTest extends TestCase
{
    private static string $message = "Custom Message";

    private static string $exception = "Custom Exception";

    public function testDefaultParameters()
    {
        $exception = new CustomException();
        $this->assertEquals(self::$exception, $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
    }

    public function testCustomParameters()
    {
        $exception = new CustomException(self::$message, 123);
        $this->assertEquals(self::$message, $exception->getMessage());
        $this->assertEquals(123, $exception->getCode());
    }

    public function testEmptyStringMessage()
    {
        $exception = new CustomException("");
        $this->assertEquals("", $exception->getMessage());
    }

    public function testNonIntegerCode()
    {
        $bool = true;
        $exception = new CustomException(self::$message, +$bool);
        $this->assertEquals(1, $exception->getCode());
    }

    public function testNegativeCode()
    {
        $exception = new CustomException(self::$message, -123);
        $this->assertEquals(-123, $exception->getCode());
    }

    public function testCustomExceptionExtendsExceptionClass()
    {
        $exception = new CustomException();
        $this->assertInstanceOf(Exception::class, $exception);
    }

    public function testCustomExceptionImplementsThrowable()
    {
        $exception = new CustomException();
        $this->assertInstanceOf(Throwable::class, $exception);
    }

    public function testCustomExceptionGetCode()
    {
        $exception = new CustomException("This is a custom exception", 500);
        $this->assertEquals(500, $exception->getCode());
    }

    public function testGetPreviousMethod()
    {
        $previousException = new Exception("Previous Exception");
        $exception = new CustomException(self::$exception, 500, $previousException);
        $this->assertEquals($previousException, $exception->getPrevious());
    }

    public function testGetMessageMethod()
    {
        $exception = new CustomException();
        $this->assertEquals(self::$exception, $exception->getMessage());
    }
}
