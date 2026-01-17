<?php

namespace GenericDatabase\Tests\Helpers;

use GenericDatabase\Helpers\Validations;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @coversDefaultClass \GenericDatabase\Helpers\Validations
 */
final class ValidationsTest extends TestCase
{
    public function testIsNumberReturnsTrue()
    {
        $value = '123';

        $result = Validations::isNumber($value);

        $this->assertTrue($result);
    }

    public function testIsNumberReturnsFalse()
    {
        $value = 'abc';

        $result = Validations::isNumber($value);

        $this->assertFalse($result);
    }

    public function testIsBooleanReturnsTrue()
    {
        $value = true;

        $result = Validations::isBoolean($value);

        $this->assertTrue($result);
    }

    public function testIsBooleanReturnsFalseForEmptyString()
    {
        $value = '';

        $result = Validations::isBoolean($value);

        $this->assertFalse($result);
    }

    public function testIsBooleanReturnsFalseForNull()
    {
        $value = null;

        $result = Validations::isBoolean($value);

        $this->assertNull($result);
    }

    public function testIsBooleanReturnsFalseForStringWithTabs()
    {
        $value = "\t\t\t";

        $result = Validations::isBoolean($value);

        $this->assertFalse($result);
    }

    public function testRandomStringGeneratedWithCorrectLength()
    {
        $length = 10;

        $result = Validations::randomString($length);

        $this->assertEquals($length, strlen($result));
    }

    public function testDetectTypesWithBoolean()
    {
        $input = [true, false];
        $expected = [1, 0];

        $result = Validations::detectTypes($input);
        $this->assertEquals($expected, $result);
    }

    public function testDetectTypesWithIntegers()
    {
        $input = [1, -1, 0];
        $expected = [1, -1, 0];

        $result = Validations::detectTypes($input);
        $this->assertEquals($expected, $result);
    }

    public function testDetectTypesWithStrings()
    {
        $input = ['hello', '123', ''];
        $expected = ['hello', '123', ''];

        $result = Validations::detectTypes($input);
        $this->assertEquals($expected, $result);
    }

    public function testDetectTypesWithArrays()
    {
        $input = [['a', 'b'], ['1', '2']];
        $expected = ['a,b', '1,2'];

        $result = Validations::detectTypes($input);
        $this->assertEquals($expected, $result);
    }

    public function testDetectTypesWithObjects()
    {
        $obj = new stdClass();
        $obj->name = 'Test';

        $input = [$obj];
        $result = Validations::detectTypes($input);

        $this->assertEquals(
            [serialize($obj)],
            $result
        );
    }

    public function testDetectTypesWithStream()
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, 'test content');
        rewind($stream);

        $input = [$stream];
        $expected = ['test content'];

        $result = Validations::detectTypes($input);
        $this->assertEquals($expected, $result);

        fclose($stream);
    }

    public function testDetectTypesWithMixedTypes()
    {
        $obj = new stdClass();
        $obj->name = 'Test';

        $input = [
            true,
            42,
            'string',
            ['a', 'b'],
            $obj,
        ];

        $expected = [
            1,
            42,
            'string',
            'a,b',
            serialize($obj),
        ];

        $result = Validations::detectTypes($input);
        $this->assertEquals($expected, $result);
    }
}
