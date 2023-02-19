<?php
require_once './src/Traits.php';
require_once './src/engine/PDO/DSN.php';
require_once './src/engine/PDO/Arguments.php';
require_once './src/engine/PDO/Attributes.php';
require_once './src/engine/PDO.php';

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
    $result = PDOAttributes::fetchAll();
    $this->assertIsArray($result);
  }

  public function testFetchAllRetrievesAllAttributes()
  {
    $result = PDOAttributes::fetchAll();
    $this->assertEquals(count(PDOAttributes::$attributeList), count((array) $result));
    foreach (PDOAttributes::$attributeList as $attribute) {
      $this->assertArrayHasKey($attribute, (array) $result);
    }
  }
}
