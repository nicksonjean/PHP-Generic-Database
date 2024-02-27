<?php

namespace GenericDatabase\Tests\Helpers;

use GenericDatabase\Helpers\INI;
use GenericDatabase\Helpers\Path;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \GenericDatabase\Helpers\INI
 */
final class INITest extends TestCase
{
    public function testValidIniFile()
    {
        $ini = 'tests/Helpers/Samples/ini/Valid.ini';
        $isValid = INI::isValidINI(Path::toAbsolute($ini));
        $this->assertTrue($isValid);
    }

    public function testEmptyIniFile()
    {
        $ini = 'tests/Helpers/Samples/ini/Empty.ini';
        $isValid = INI::isValidINI(Path::toAbsolute($ini));
        $this->assertTrue($isValid);
    }

    public function testNonStringIni()
    {
        $ini = 0;
        $isValid = INI::isValidINI($ini);
        $this->assertFalse($isValid);
    }

    public function testSimpleIniFile()
    {
        $ini = 'tests/Helpers/Samples/ini/Simple.ini';
        $isValid = INI::isValidINI(Path::toAbsolute($ini));
        $this->assertTrue($isValid);
    }

    public function testComplexIniFile()
    {
        $ini = 'tests/Helpers/Samples/ini/Complex.ini';
        $isValid = INI::isValidINI(Path::toAbsolute($ini));
        $this->assertTrue($isValid);
    }

    public function testNonStringArgument()
    {
        $ini = '123';
        $isValid = INI::isValidINI(Path::toAbsolute($ini));
        $this->assertFalse($isValid);
    }

    public function testParseIniWithInvalidIniFile()
    {
        $ini = 'tests/Helpers/Samples/ini/Valid.ini';
        $result = INI::parseINI($ini);
        $this->assertEquals(['name' => 'John Doe', 'organization' => 'Acme Widgets Inc.'], $result);
    }

    public function testWithCustomParserWithIniValidIniFile()
    {
        $ini = 'tests/Helpers/Samples/ini/Valid.ini';
        $result = INI::parseIniFile($ini);
        $this->assertEquals(['owner' => ['name' => 'John Doe', 'organization' => 'Acme Widgets Inc.']], $result);
    }

    public function testWithCustomParserWithIniEmptyIniFile()
    {
        $ini = 'tests/Helpers/Samples/ini/EmptyLine.ini';
        $result = INI::parseIniFile($ini);
        $this->assertEquals([], $result);
    }

    public function testParseIniWithSectionButNoKeyValuePairs()
    {
        $filepath = 'tests/Helpers/Samples/ini/SectionOnly.ini';
        $expectedResult = [];
        $result = INI::parseIniFile($filepath);
        $this->assertEquals($expectedResult, $result);
    }

    public function testWithCustomParserWithACommentedIniEmptyIniFile()
    {
        $ini = 'tests/Helpers/Samples/ini/NoSection.ini';
        $result = INI::parseIniFile($ini);
        $this->assertEquals(['name' => 'John Doe', 'organization' => 'Acme Widgets Inc.'], $result);
    }
}
