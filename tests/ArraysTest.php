<?php

use GenericDatabase\Helpers\Arrays;
use PHPUnit\Framework\TestCase;

final class ArraysTest extends TestCase
{
    public function test_exceptByKeys_happyPath()
    {
        $array = ['a' => 1, 'b' => 2, 'c' => 3];
        $keys = ['a', 'c'];
        $expectedResult = ['b' => 2];

        $result = Arrays::exceptByKeys($array, $keys);

        $this->assertEquals($expectedResult, $result);
    }

    public function test_exceptByValues_happyPath()
    {
        $array = ['a', 'b', 'c'];
        $values = ['b', 'c'];
        $expectedResult = ['a'];

        $result = Arrays::exceptByValues($array, $values);

        $this->assertEquals($expectedResult, $result);
    }

    public function test_isMultidimensional_happyPath()
    {
        $multidimensionalArray = [['a' => 1], ['b' => 2]];
        $nonMultidimensionalArray = ['a', 'b', 'c'];

        $multidimensionalResult = Arrays::isMultidimensional($multidimensionalArray);
        $nonMultidimensionalResult = Arrays::isMultidimensional($nonMultidimensionalArray);

        $this->assertTrue($multidimensionalResult);
        $this->assertFalse($nonMultidimensionalResult);
    }

    public function test_arrayValuesRecursive_happyPath()
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

    public function test_arrayAssocToIndex_happyPath()
    {
        // Arrange
        $array = ['a', 'b', 'c'];
        $expectedResult = [0 => 'a', 1 => 'b', 2 => 'c'];

        // Act
        $result = Arrays::assocToIndex($array);

        // Assert
        $this->assertEquals($expectedResult, $result);
    }

    public function test_isArrayAssoc_happyPath()
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

    public function test_arrayAssocToIndex_emptyArray()
    {
        // Arrange
        $array = ['a' => 'b'];
        $expectedResult = [0 => 'b'];

        // Act
        $result = Arrays::assocToIndex($array);

        // Assert
        $this->assertEquals($expectedResult, $result);
    }

    public function test_arrayValuesRecursive_emptyArray()
    {
        $array = [];
        $expected = [];
        $actual = Arrays::arrayValuesRecursive($array);
        $this->assertEquals($expected, $actual);
    }

    public function test_arrayValuesRecursive_nonEmptyArray()
    {
        $array = [1];
        $expected = [1];
        $actual = Arrays::arrayValuesRecursive($array);
        $this->assertEquals($expected, $actual);
    }

    public function test_isArrayAssoc_emptyArray()
    {
        $array = [];
        $expectedResult = false;

        $result = Arrays::isAssoc($array);

        $this->assertEquals($expectedResult, $result);
    }

    public function test_exceptByKeys_emptyArray()
    {
        $array = ['a' => 1, 'b' => 2, 'c' => 3];
        $keys = ['a', 'b', 'c'];
        $expectedResult = [];

        $result = Arrays::exceptByKeys($array, $keys);

        $this->assertEquals($expectedResult, $result);
    }

    public function test_exceptByValues_emptyArray()
    {
        $array = ['a', 'b', 'c'];
        $values = ['a', 'b', 'c'];
        $expectedResult = [];

        $result = Arrays::exceptByValues($array, $values);

        $this->assertEquals($expectedResult, $result);
    }
}
