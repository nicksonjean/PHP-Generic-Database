<?php

namespace GenericDatabase\Tests\Helpers;

use GenericDatabase\Helpers\CustomException;
use GenericDatabase\Helpers\Path;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \GenericDatabase\Helpers\Path
 */
final class PathTest extends TestCase
{
    public function testIsAbsoluteWithEmptyPath()
    {
        $emptyPath = '';
        $this->expectException(CustomException::class);
        Path::isAbsolute($emptyPath);
    }

    public function testIsAbsoluteWithValidAbsolutePath()
    {
        $absolutePath = '/var/www/html';
        $result = Path::isAbsolute($absolutePath);
        $this->assertTrue($result);
    }

    public function testIsAbsoluteWithValidWindowsAbsolutePath()
    {
        $windowsAbsolutePath = 'C:/Users/User/Documents';
        $result = Path::isAbsolute($windowsAbsolutePath);
        $this->assertTrue($result);
    }

    public function testIsAbsoluteWithRelativePath()
    {
        $relativePath = 'path/to/some/file.txt';
        $result = Path::isAbsolute($relativePath);
        $this->assertFalse($result);
    }

    public function testIsAbsoluteWithInvalidPath()
    {
        $invalidPath = "\x00";
        $this->expectException(CustomException::class);
        Path::isAbsolute($invalidPath);
    }

    public function testThrowsExceptionForInvalidPath()
    {
        $result = Path::isAbsolute('relative-path');
        $this->assertFalse($result);
    }

    public function testToAbsolutePath()
    {
        $this->assertEquals(dirname(__FILE__), Path::toAbsolute('/tests/Helpers'));
    }
}
