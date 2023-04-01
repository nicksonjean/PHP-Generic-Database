<?php

namespace GenericDatabase\Engine\PDO;

use
  GenericDatabase\Traits\Path,
  GenericDatabase\Engine\PDOEngine;

class DSN
{
  public static function parseDns(): string|\Exception
  {
    if (!in_array(PDOEngine::getInstance()->getDriver(), (array) \PDO::getAvailableDrivers())) {
      $message = sprintf(
        "Driver '%s' is invalid, set the driver property with one of these options: '%s'",
        [PDOEngine::getInstance()->getDriver(), implode(', ', (array) \PDO::getAvailableDrivers())]
      );
      throw new \Exception($message);
    }
    $result = null;
    switch (PDOEngine::getInstance()->getDriver()) {
      case 'mysql':
        $result = self::DSNPDOMySQL();
        break;

      case 'pgsql':
        $result = self::DSNPDOPGSQL();
        break;

      case 'oci':
      case 'dblib':
      case 'sybase':
        $result = self::DSNPDOOCI();
        break;

      case 'sqlsrv':
        $result = self::DSNPDOSQLSRV();
        break;

      case 'mssql':
        $result = self::DSNPDOMSSQL();
        break;

      case 'ibase':
      case 'firebird':
        $result = self::DSNPDOIBASE();
        break;

      case 'sqlite':
        $result = self::DSNPDOSQLite();
        break;
    }
    PDOEngine::getInstance()->setDsn((string) $result);
    return $result;
  }

  private static function DSNPDOMySQL(): string
  {
    return sprintf(
      "%s:host=%s;dbname=%s;port=%s;charset=%s",
      PDOEngine::getInstance()->getDriver(),
      PDOEngine::getInstance()->getHost(),
      PDOEngine::getInstance()->getDatabase(),
      PDOEngine::getInstance()->getPort(),
      PDOEngine::getInstance()->getCharset()
    );
  }

  private static function DSNPDOPGSQL(): string
  {
    return sprintf(
      "%s:host=%s;dbname=%s;port=%s;user=%s;password=%s;options='--client_encoding=%s'",
      PDOEngine::getInstance()->getDriver(),
      PDOEngine::getInstance()->getHost(),
      PDOEngine::getInstance()->getDatabase(),
      PDOEngine::getInstance()->getPort(),
      PDOEngine::getInstance()->getUser(),
      PDOEngine::getInstance()->getPassword(),
      PDOEngine::getInstance()->getCharset()
    );
  }

  private static function DSNPDOOCI(): string
  {
    return sprintf(
      "%s:host=%s:%s/%s;charset=%s",
      PDOEngine::getInstance()->getDriver(),
      PDOEngine::getInstance()->getHost(),
      PDOEngine::getInstance()->getPort(),
      PDOEngine::getInstance()->getDatabase(),
      PDOEngine::getInstance()->getCharset()
    );
  }

  private static function DSNPDOSQLSRV(): string
  {
    return sprintf(
      "%s:server=%s,%s;database=%s",
      PDOEngine::getInstance()->getDriver(),
      PDOEngine::getInstance()->getHost(),
      PDOEngine::getInstance()->getPort(),
      PDOEngine::getInstance()->getDatabase()
    );
  }

  private static function DSNPDOMSSQL(): string
  {
    if (PHP_OS == 'WIN') {
      PDOEngine::getInstance()->setDriver('sqlsrv');
      return sprintf(
        "%s:server=%s,%s;database=%s",
        PDOEngine::getInstance()->getDriver(),
        PDOEngine::getInstance()->getHost(),
        PDOEngine::getInstance()->getPort(),
        PDOEngine::getInstance()->getDatabase()
      );
    } else {
      PDOEngine::getInstance()->setDriver('dblib');
      return sprintf(
        "%s:host=%s:%s/%s;charset=%s",
        PDOEngine::getInstance()->getDriver(),
        PDOEngine::getInstance()->getHost(),
        PDOEngine::getInstance()->getPort(),
        PDOEngine::getInstance()->getDatabase(),
        PDOEngine::getInstance()->getCharset()
      );
    }
  }

  private static function DSNPDOIBASE(): string
  {
    if (!Path::isAbsolute(PDOEngine::getInstance()->getDatabase())) {
      PDOEngine::getInstance()->setDatabase(Path::toAbsolute(PDOEngine::getInstance()->getDatabase()));
    }
    return sprintf(
      "%s:dbname=%s/%s:%s;charset=%s",
      PDOEngine::getInstance()->getDriver(),
      PDOEngine::getInstance()->getHost(),
      PDOEngine::getInstance()->getPort(),
      PDOEngine::getInstance()->getDatabase(),
      PDOEngine::getInstance()->getCharset()
    );
  }

  private static function DSNPDOSQLite(): string
  {
    if (!Path::isAbsolute(PDOEngine::getInstance()->getDatabase()) && PDOEngine::getInstance()->getDatabase() !== 'memory') {
      PDOEngine::getInstance()->setDatabase(Path::toAbsolute(PDOEngine::getInstance()->getDatabase()));
      return sprintf(
        "%s:%s",
        PDOEngine::getInstance()->getDriver(),
        PDOEngine::getInstance()->getDatabase()
      );
    } else {
      return sprintf(
        "%s::%s:",
        PDOEngine::getInstance()->getDriver(),
        PDOEngine::getInstance()->getDatabase()
      );
    }
  }
}
