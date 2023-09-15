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
        $ini = 'tests/Helpers/Samples/INI/Valid.ini';
        $isValid = INI::isValidINI(Path::toAbsolute($ini));
        $this->assertTrue($isValid);
    }

    public function testEmptyIniFile()
    {
        $ini = 'tests/Helpers/Samples/INI/Empty.ini';
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
        $ini = 'tests/Helpers/Samples/INI/Simple.ini';
        $isValid = INI::isValidINI(Path::toAbsolute($ini));
        $this->assertTrue($isValid);
    }

    public function testComplexIniFile()
    {
        $ini = 'tests/Helpers/Samples/INI/Complex.ini';
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
        $ini = 'tests/Helpers/Samples/INI/Valid.ini';
        $result = INI::parseINI($ini);
        $this->assertEquals(['name' => 'John Doe', 'organization' => 'Acme Widgets Inc.'], $result);
    }
}
