<?php

namespace GenericDatabase\Tests\Helpers;

use GenericDatabase\Helpers\Types\Compounds\Arrays;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \GenericDatabase\Helpers\Arrays
 */
final class ArraysTest extends TestCase
{
    private static string $defaultQuery = 'SELECT * FROM users WHERE id = :id AND name = :name';

    private static array $defaultParams = [':id' => 1, ':name' => 'John'];

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

    public function testMatchValues()
    {
        $list = ["apple", "banana", "cherry"];
        $array = ["apple", "Banana", "Cherry"];
        $expectedResult = "apple";

        $result = Arrays::matchValues($list, $array);

        $this->assertEquals($expectedResult, $result);
    }

    public function testRecombine()
    {
        $array = ['name' => 'John', 'age' => 30, 'city' => 'Las Vegas'];
        $inputArray = $array;
        $expectedResult = $array;

        $result = Arrays::recombine($inputArray);

        $this->assertEquals($expectedResult, $result);
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

    public function testArrayValuesRecursiveMassiveArrays()
    {
        $inputArray = [
            'name' => 'Robocop',
            'details' => [
                'age' => 30,
                'city' => 'Detroit',
            ],
            'hobbies' => [
                'outdoor' => ['hiking', 'camping'],
                'indoor' => ['reading', 'cooking'],
            ],
        ];

        $expectedResult = [
            'name' => 'Robocop',
            'details' => [
                0 => 30,
                1 => 'Detroit',
            ],
            'hobbies' => [
                0 => ['hiking', 'camping'],
                1 => ['reading', 'cooking'],
            ],
        ];

        $result = Arrays::arrayValuesRecursive($inputArray);

        $this->assertEquals($expectedResult, $result);
    }

    public function testArrayAssocToIndex()
    {
        $array = ['a', 'b', 'c'];
        $expectedResult = [0 => 'a', 1 => 'b', 2 => 'c'];

        $result = Arrays::assocToIndex($array);

        $this->assertEquals($expectedResult, $result);
    }

    public function testIsArrayAssoc()
    {
        $associativeArray = ['a' => 1, 'b' => 2, 'c' => 3];
        $indexedArray = [1, 2, 3];

        $isAssociativeArray = Arrays::isAssoc($associativeArray);
        $isIndexedArray = Arrays::isAssoc($indexedArray);

        $this->assertTrue($isAssociativeArray);
        $this->assertFalse($isIndexedArray);
    }

    public function testArrayAssocToIndexEmptyArray()
    {
        $array = ['a' => 'b'];
        $expectedResult = [0 => 'b'];

        $result = Arrays::assocToIndex($array);

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

        $result = Arrays::isAssoc($array);

        $this->assertEquals(false, $result);
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

    public function testAssocToIndexCombine()
    {
        $array1 = ['fruta' => 'apple', 'veiculo' => 'bicicleta'];
        $expectedResult = [0 => 'apple', 'fruta' => 'apple', 1 => 'bicicleta', 'veiculo' => 'bicicleta'];

        $result = Arrays::assocToIndexCombine($array1);

        $this->assertEquals($expectedResult, $result);
    }

    public function testMakeArgsDefault()
    {
        $params = [
            'sql statement',
            self::$defaultQuery,
            self::$defaultParams
        ];

        $result = Arrays::makeArgs('other_driver', ...$params);

        $expectedResult = [
            'sqlStatement' => 'sql statement',
            'sqlQuery' => self::$defaultQuery,
            'sqlArgs' => self::$defaultParams,
            'isArray' => true,
            'isMulti' => false,
            'isArgs' => false
        ];

        $this->assertEquals($expectedResult, $result);
    }

    public function testMakeArrayForSqlsrv()
    {
        $params = [
            self::$defaultQuery,
            self::$defaultParams,
        ];

        $result = Arrays::makeArgs('sqlsrv', ...$params);

        $expectedResult = [
            'sqlStatement' => null,
            'sqlQuery' => self::$defaultQuery,
            'sqlArgs' => self::$defaultParams,
            'isArray' => true,
            'isMulti' => false,
            'isArgs' => false
        ];

        $this->assertEquals($expectedResult, $result);
    }

    public function testMakeArgsForSqlsrv()
    {
        $params = [
            self::$defaultQuery,
            1,
            'John',
        ];

        $result = Arrays::makeArgs('sqlsrv', ...$params);

        $expectedResult = [
            'sqlStatement' => null,
            'sqlQuery' => self::$defaultQuery,
            'sqlArgs' => self::$defaultParams,
            'isArray' => false,
            'isMulti' => false,
            'isArgs' => true
        ];

        $this->assertEquals($expectedResult, $result);
    }

    public function testGroupByStringKey()
    {
        $arr = [
            ['name' => 'John', 'age' => 20],
            ['name' => 'Mary', 'age' => 25],
            ['name' => 'Bob', 'age' => 30],
            ['name' => 'Alice', 'age' => 35],
        ];

        $expected = [
            'John' => [['name' => 'John', 'age' => 20]],
            'Mary' => [['name' => 'Mary', 'age' => 25]],
            'Bob' => [['name' => 'Bob', 'age' => 30]],
            'Alice' => [['name' => 'Alice', 'age' => 35]],
        ];

        $result = Arrays::arrayGroupBy($arr, 'name');

        $this->assertEquals($expected, $result);
    }

    public function testGroupByCallableKey()
    {
        $arr = [
            ['name' => 'John', 'age' => 20],
            ['name' => 'Mary', 'age' => 25],
            ['name' => 'Bob', 'age' => 30],
            ['name' => 'Alice', 'age' => 35],
        ];

        $expected = [
            20 => [['name' => 'John', 'age' => 20]],
            25 => [['name' => 'Mary', 'age' => 25]],
            30 => [['name' => 'Bob', 'age' => 30]],
            35 => [['name' => 'Alice', 'age' => 35]],
        ];

        $result = Arrays::arrayGroupBy($arr, function ($item) {
            return $item['age'];
        });

        $this->assertEquals($expected, $result);
    }
}
