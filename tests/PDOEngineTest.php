<?php

use
  GenericDatabase\Engine\PDOEngine;

use PHPUnit\Framework\TestCase;

final class PDOEngineTest extends TestCase
{
  private static $instance;

  public static function setUpBeforeClass(): void
  {
    self::$instance = PDOEngine
      ::setDriver('mysql')
      ::setHost('localhost')
      ::setPort(3306)
      ::setDatabase('demodev')
      ::setUser('root')
      ::setPassword('')
      ::setCharset('utf8')
      ::setOptions([
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
      ])
      ::setException(true)
      ->connect();
  }

  public function testConnect(): void
  {
    $this->assertInstanceOf(PDOEngine::class, self::$instance);
  }

  public function testGetConnection()
  {
    $this->assertNotEmpty(self::$instance->getConnection());
  }

  public function testLoadFromFile(): void
  {
    $this->assertGreaterThanOrEqual(1, self::$instance->loadFromFile('./tests/test.sql'));
  }

  // public function testDsn()
  // {
  //   $this->assertNotEmpty(self::$instance->getDsn('host', 'database', 'user', 'password', 'driver'));
  // }

  // public function testBeginTransaction()
  // {
  //   $this->assertNull(self::$instance->beginTransaction());
  // }

  // public function testCommit()
  // {
  //   $this->assertNull(self::$instance->commit());
  // }

  // public function testRollback()
  // {
  //   $this->assertNull(self::$instance->rollback());
  // }
}
