<?php

namespace GenericDatabase\Tests\Engine\SQLite;

use GenericDatabase\Engine\SQLite\SQLite;
use GenericDatabase\Engine\SQLite\Attributes;
use GenericDatabase\Engine\SQLiteEngine;
use GenericDatabase\Helpers\CustomException;
use PHPUnit\Framework\TestCase;

final class AttributesTest extends TestCase
{
    private static $instance;

    private static $settings;

    public static function setUpBeforeClass(): void
    {
        self::$instance = SQLiteEngine
            ::setDriver('sqlite')
            ::setDatabase('memory')
            ::setCharset('utf8')
            ::setOptions([
                SQLite::ATTR_OPEN_READONLY => false,
                SQLite::ATTR_OPEN_READWRITE => true,
                SQLite::ATTR_OPEN_CREATE => true,
                SQLite::ATTR_CONNECT_TIMEOUT => 28800,
                SQLite::ATTR_PERSISTENT => false,
                SQLite::ATTR_AUTOCOMMIT => true
            ])
            ::setException(true)
            ->connect();

        $version = \SQLite3::version();
        self::$settings = [
            'versionString' => $version['versionString'],
            'versionNumber' => $version['versionNumber']
        ];
    }

    public static function tearDownAfterClass(): void
    {
        self::$instance->disconnect();
    }

    public function testDefineAttributes()
    {
        Attributes::define();
        $attributes = self::$instance->getAttributes();
        $expectedAttributes = [
            'AUTOCOMMIT' => 0,
            'ERRMODE' => 1,
            'CASE' => 0,
            'CLIENT_VERSION' => self::$settings['versionString'],
            'CONNECTION_STATUS' => 'Connection OK in memory; waiting to send.',
            'PERSISTENT' => 0,
            'SERVER_INFO' => '',
            'SERVER_VERSION' => self::$settings['versionNumber'],
            'TIMEOUT' => 28800,
            'EMULATE_PREPARES' => true,
            'DEFAULT_FETCH_MODE' => 3
        ];
        $this->assertEquals($expectedAttributes, $attributes);
    }

    public function testGetAllAttributesReturnsWithArray()
    {
        $this->assertIsArray(self::$instance->getAttributes());
    }

    public function testGetSpecificAttributes()
    {
        $result = self::$instance->getAttributes();
        $this->assertArrayHasKey('ERRMODE', (array) $result);
    }

    public function testRetrievesAllAttributes()
    {
        Attributes::define();
        $attributes = self::$instance->getAttributes();
        foreach ($attributes as $key => $value) {
            $this->assertEquals($value, self::$instance->attributes[$key]);
        }
    }

    public function testThrowExceptionInvalidAttribute()
    {
        $this->expectException(CustomException::class);
        $this->expectExceptionMessage("Invalid attribute: INVALID_ATTRIBUTE");
        $originalAttributeList = Attributes::$attributeList;
        Attributes::$attributeList[] = 'INVALID_ATTRIBUTE';
        try {
            Attributes::define();
        } finally {
            Attributes::$attributeList = $originalAttributeList;
        }
    }
}
