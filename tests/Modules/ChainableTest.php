<?php

namespace GenericDatabase\Tests\Modules;

use PHPUnit\Framework\TestCase;
use GenericDatabase\Connection;
use GenericDatabase\Modules\Chainable;
use GenericDatabase\Engine\MySQLiEngine;
use GenericDatabase\Engine\MySQLi\MySQL;

class ChainableTest extends TestCase
{
    private array $env;

    protected function setUp(): void
    {
        $this->env = [
            'MYSQL_HOST' => 'localhost',
            'MYSQL_PORT' => '3306',
            'MYSQL_DATABASE' => 'demodev',
            'MYSQL_USER' => 'root',
            'MYSQL_PASSWORD' => 'masterkey',
            'MYSQL_CHARSET' => 'utf8',
        ];
    }

    public function testNativeMysqli()
    {
        $result = Chainable::nativeMySQLi($this->env, false, false);
        $this->assertInstanceOf(MySQLiEngine::class, $result);
        $this->assertEquals('localhost', $result->getHost());
        $this->assertEquals(3306, $result->getPort());
        $this->assertEquals('demodev', $result->getDatabase());
        $this->assertEquals('root', $result->getUser());
        $this->assertEquals('masterkey', $result->getPassword());
        $this->assertEquals('utf8', $result->getCharset());
        $options = [
            MySQL::ATTR_PERSISTENT => false,
            MySQL::ATTR_AUTOCOMMIT => true,
            MySQL::ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
            MySQL::ATTR_SET_CHARSET_NAME => "utf8",
            MySQL::ATTR_OPT_INT_AND_FLOAT_NATIVE => true,
            MySQL::ATTR_OPT_CONNECT_TIMEOUT => 28800,
            MySQL::ATTR_OPT_READ_TIMEOUT => 30,
            MySQL::ATTR_READ_DEFAULT_GROUP => "MAX_ALLOWED_PACKET=50M"
        ];
        $this->assertEquals($options, $result->getOptions());
        $this->assertTrue($result->getException());
    }
}
