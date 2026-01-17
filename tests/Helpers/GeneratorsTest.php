<?php

namespace GenericDatabase\Tests\Helpers;

use GenericDatabase\Helpers\Generators;
use GenericDatabase\Engine\MySQLiConnection;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;

/**
 * @coversDefaultClass \GenericDatabase\Helpers\Arrays
 */
final class GeneratorsTest extends TestCase
{
    public function testSetConstant()
    {
        $value = [
            0 => [
                'MySQL::ATTR_PERSISTENT' => true,
                'MySQL::ATTR_AUTOCOMMIT' => true,
                'MySQL::ATTR_INIT_COMMAND' => "SET NAMES 'utf8'",
                'MySQL::ATTR_SET_CHARSET_NAME' => 'utf8',
                'MySQL::ATTR_OPT_INT_AND_FLOAT_NATIVE' => true,
                'MySQL::ATTR_OPT_CONNECT_TIMEOUT' => 28800,
                'MySQL::ATTR_OPT_READ_TIMEOUT' => 30,
                'MySQL::ATTR_READ_DEFAULT_GROUP' => 'MAX_ALLOWED_PACKET=50M'
            ]
        ];

        $options = Generators::setConstant(
            $value,
            MySQLiConnection::getInstance(),
            'MySQL',
            'MySQLi',
            ['ATTR_PERSISTENT', 'ATTR_AUTOCOMMIT']
        );

        $expectedOptions = [
            13 => true,
            14 => true,
            1002 => "SET NAMES 'utf8'",
            4 => 'utf8',
            1011 => true,
            2 => 28800,
            3 => 30,
            1007 => 'MAX_ALLOWED_PACKET=50M'
        ];

        $this->assertEquals($expectedOptions, $options);
    }

    public function testSetType()
    {
        $this->assertEquals(123, Generators::setType(123));
        $this->assertEquals(true, Generators::setType(true));
        $this->assertEquals(false, Generators::setType(false));
        $this->assertEquals('string', Generators::setType('string'));
        $this->assertEquals('', Generators::setType(null));
        $this->assertEquals(0, Generators::setType('0'));
        $this->assertEquals(1, Generators::setType('1'));
    }

    /**
     * @throws ReflectionException
     */
    public function testGenerateKeyName()
    {
        $reflectionClass = new ReflectionClass(Generators::class);
        $method = $reflectionClass->getMethod('generateKeyName');
        $method->setAccessible(true); //NOSONAR
        $resultA = $method->invokeArgs(null, ['ATTR_USER', 'SQLite']);
        $resultB = $method->invokeArgs(null, ['ATTR_PASS', 'MySQL']);

        $this->assertEquals('SQLITE3_USER', $resultA);
        $this->assertEquals('MYSQL_PASS', $resultB);
    }

    /**
     * @throws ReflectionException
     */
    public function testGenerateOptionKey()
    {
        $reflectionClass = new ReflectionClass(Generators::class);
        $method = $reflectionClass->getMethod('generateOptionKey');
        $method->setAccessible(true); //NOSONAR
        $resultA = $method->invokeArgs(null, ['MySQL', 'MySQLi', 'ATTR_PERSISTENT']);
        $resultB = $method->invokeArgs(null, ['MySQL', 'MySQLi', 'ATTR_AUTOCOMMIT']);

        $this->assertEquals('13', $resultA);
        $this->assertEquals('14', $resultB);
    }
}
