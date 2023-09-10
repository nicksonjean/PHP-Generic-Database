<?php

use GenericDatabase\Helpers\Arrays;
use PHPUnit\Framework\TestCase;

final class ArraysTest extends TestCase
{
    public function testExceptByKeys()
    {
        $array = ['a' => 1, 'b' => 2, 'c' => 3];
        $keys = ['a', 'c'];
        $expectedResult = ['b' => 2];

        $result = Arrays::exceptByKeys($array, $keys);

        $this->assertEquals($expectedResult, $result);
    }

    public function testExceptByValue()
    {
        $array = ['a', 'b', 'c'];
        $values = ['b', 'c'];
        $expectedResult = ['a'];

        $result = Arrays::exceptByValues($array, $values);

        $this->assertEquals($expectedResult, $result);
    }

    public function testIsMultidimensional()
    {
        $multArray = [['a' => 1], ['b' => 2]];
        $nonMultArray = ['a', 'b', 'c'];

        $multResult = Arrays::isMultidimensional($multArray);
        $nonmultResult = Arrays::isMultidimensional($nonMultArray);

        $this->assertTrue($multResult);
        $this->assertFalse($nonmultResult);
    }

    public function testArrayValuesRecursive()
    {
        $array = [];
        $expectedResult = [];

        $result = Arrays::arrayValuesRecursive($array);

        $this->assertEquals(
            $expectedResult,
            $result,
            'The function did not return the expected result for an empty array.'
        );
    }

    public function testArrayAssocToIndex()
    {
        // Arrange
        $array = ['a', 'b', 'c'];
        $expectedResult = [0 => 'a', 1 => 'b', 2 => 'c'];

        // Act
        $result = Arrays::assocToIndex($array);

        // Assert
        $this->assertEquals($expectedResult, $result);
    }

    public function testIsArrayAssoc()
    {
        // Arrange
        $associativeArray = ['a' => 1, 'b' => 2, 'c' => 3];
        $indexedArray = [1, 2, 3];

        // Act
        $isAssociativeArray = Arrays::isAssoc($associativeArray);
        $isIndexedArray = Arrays::isAssoc($indexedArray);

        // Assert
        $this->assertTrue($isAssociativeArray);
        $this->assertFalse($isIndexedArray);
    }

    public function testArrayAssocToIndexEmptyArray()
    {
        // Arrange
        $array = ['a' => 'b'];
        $expectedResult = [0 => 'b'];

        // Act
        $result = Arrays::assocToIndex($array);

        // Assert
        $this->assertEquals($expectedResult, $result);
    }

    public function testArrayValuesRecursiveEmptyArray()
    {
        $array = [];
        $expected = [];
        $actual = Arrays::arrayValuesRecursive($array);
        $this->assertEquals($expected, $actual);
    }

    public function testArrayValuesRecursiveNonEmptyArray()
    {
        $array = [1];
        $expected = [1];
        $actual = Arrays::arrayValuesRecursive($array);
        $this->assertEquals($expected, $actual);
    }

    public function testIsArrayAssocEmptyArray()
    {
        $array = [];
        $expectedResult = false;

        $result = Arrays::isAssoc($array);

        $this->assertEquals($expectedResult, $result);
    }

    public function testExceptByKeysEmptyArray()
    {
        $array = ['a' => 1, 'b' => 2, 'c' => 3];
        $keys = ['a', 'b', 'c'];
        $expectedResult = [];

        $result = Arrays::exceptByKeys($array, $keys);

        $this->assertEquals($expectedResult, $result);
    }

    public function testExceptByValuesEmptyArray()
    {
        $array = ['a', 'b', 'c'];
        $values = ['a', 'b', 'c'];
        $expectedResult = [];

        $result = Arrays::exceptByValues($array, $values);

        $this->assertEquals($expectedResult, $result);
    }
}
