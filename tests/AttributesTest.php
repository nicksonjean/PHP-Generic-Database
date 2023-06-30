<?php

use
  GenericDatabase\Engine\PDOEngine,
  GenericDatabase\Engine\PDO\Attributes;

use PHPUnit\Framework\TestCase;

final class AttributesTest extends TestCase
{
  private static $instance;

  public static function setUpBeforeClass(): void
  {
    self::$instance = PDOEngine
      ::setDriver('sqlite')
      ::setDatabase('memory')
      ::setCharset('utf8')
      ::setOptions([
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
      ])
      ::setException(true)
      ->connect();
  }

  public static function tearDownAfterClass(): void
  {
    self::$instance = null;
  }

  public function testFetchAllReturnsArray()
  {
    $this->assertIsArray(self::$instance->getAttributes());
  }

  public function testFetchAllRetrievesAllAttributes()
  {
    $result = self::$instance->getAttributes();
    $this->assertArrayHasKey('ERRMODE', $result);
  }
}
