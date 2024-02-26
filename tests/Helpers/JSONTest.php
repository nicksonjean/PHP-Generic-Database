<?php

namespace GenericDatabase\Tests\Helpers;

use GenericDatabase\Helpers\JSON;
use GenericDatabase\Helpers\Path;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \GenericDatabase\Helpers\INI
 */
final class JSONTest extends TestCase
{
    public function testValidJsonString()
    {
        $json = 'tests/Helpers/Samples/json/ValidObject.json';
        $isValid = JSON::isValidJSON(Path::toAbsolute($json));
        $this->assertTrue($isValid);
    }

    public function testEmptyObjectJsonString()
    {
        $json = 'tests/Helpers/Samples/json/EmptyObject.json';
        $isValid = JSON::isValidJSON($json);
        $this->assertTrue($isValid);
    }

    public function testNonStringJson()
    {
        $json = 0;
        $isValid = JSON::isValidJSON($json);
        $this->assertFalse($isValid);
    }

    public function testWhitespaceJsonString()
    {
        $json = '   ';
        $isValid = JSON::isValidJSON($json);
        $this->assertFalse($isValid);
    }

    public function testSingleNumericArrayJsonString()
    {
        $json = 'tests/Helpers/Samples/json/SingleNumericArray.json';
        $isValid = JSON::isValidJSON($json);
        $this->assertTrue($isValid);
    }

    public function testMultipleValuesJsonString()
    {
        $json = 'tests/Helpers/Samples/json/MultipleObject.json';
        $isValid = JSON::isValidJSON($json);
        $this->assertTrue($isValid);
    }

    public function testInvalidJsonString()
    {
        $json = 'tests/Helpers/Samples/json/InvalidObject.json';
        $isValid = JSON::isValidJSON($json);
        $this->assertFalse($isValid);
    }

    public function testJsonWithUnicodeCharacter()
    {
        $json = 'tests/Helpers/Samples/json/UnicodeObjectString.json';
        $isValid = JSON::isValidJSON($json);
        $this->assertTrue($isValid);
    }

    public function testValidJsonStringWithNumericValue()
    {
        $json = 'tests/Helpers/Samples/json/NumericObject.json';
        $isValid = JSON::isValidJSON($json);
        $this->assertTrue($isValid);
    }

    public function testValidJsonStringWithBooleanValue()
    {
        $json = 'tests/Helpers/Samples/json/ObjectBoolean.json';
        $isValid = JSON::isValidJSON($json);
        $this->assertTrue($isValid);
    }

    public function testValidJsonStringWithNullValue()
    {
        $json = 'tests/Helpers/Samples/json/ObjectNull.json';
        $isValid = JSON::isValidJSON($json);
        $this->assertTrue($isValid);
    }

    public function testValidJsonArrayString()
    {
        $json = 'tests/Helpers/Samples/json/MultipleNumericArray.json';
        $isValid = JSON::isValidJSON($json);
        $this->assertTrue($isValid);
    }

    public function testNestedJsonString()
    {
        $json = 'tests/Helpers/Samples/json/NestedObject.json';
        $expectedResult = [
            "name" => "John",
            "age" => 30,
            "address" => [
                "street" => "123 Main St",
                "city" => "New York"
            ],
            "hobbies" => [
                "reading",
                "painting"
            ]
        ];
        $parsedJson = JSON::parseJSON($json);
        $this->assertEquals($expectedResult, $parsedJson);
    }

    public function testLargeNumberOfElements()
    {
        $json = 'tests/Helpers/Samples/json/ManyObjects.json';
        $expectedArray = [
            "name" => "John",
            "age" => 30,
            "city" => "New York",
            "address" => "123 Main St",
            "phone" => "555-1234",
            "email" => "john@example.com",
            "company" => "ABC Inc",
            "position" => "Manager",
            "salary" => 50000,
            "department" => "Sales",
            "projects" => ["Project A", "Project B", "Project C"],
            "colleagues" => [
                ["name" => "Jane", "age" => 28, "position" => "Assistant"],
                ["name" => "Mike", "age" => 32, "position" => "Developer"],
                ["name" => "Lisa", "age" => 35, "position" => "Designer"]
            ],
            "metadata" => [
                "created_at" => "2022-01-01",
                "updated_at" => "2022-01-10"
            ]
        ];
        $parsedArray = JSON::parseJSON($json);
        $this->assertEquals($expectedArray, $parsedArray);
    }
}
