<?php

namespace GenericDatabase\Engine\PgSQL;

use
  GenericDatabase\Engine\PgSQLEngine,
  GenericDatabase\Engine\PgSQL\Options;

class Attributes
{
  /**
   * static attributes constants
   * 
   */
  public static $attributeList = [
    'AUTOCOMMIT',
    'ERRMODE',
    'CASE',
    'CLIENT_VERSION',
    'CONNECTION_STATUS',
    'PERSISTENT',
    'SERVER_INFO',
    'SERVER_VERSION',
    'TIMEOUT',
    'EMULATE_PREPARES',
    'DEFAULT_FETCH_MODE',
    'CHARACTER_SET',
    'COLLATION'
  ];

  public static function getFlags()
  {
    $flags = null;
    if (Options::getOptions(PgSQL::ATTR_CONNECT_FORCE_NEW)) {
      $flags = PGSQL_CONNECT_FORCE_NEW;
    } else if (Options::getOptions(PgSQL::ATTR_CONNECT_ASYNC)) {
      $flags = PGSQL_CONNECT_ASYNC;
    } else if (Options::getOptions(PgSQL::ATTR_CONNECT_ASYNC) && Options::getOptions(PgSQL::ATTR_CONNECT_FORCE_NEW)) {
      $flags = PGSQL_CONNECT_ASYNC | PGSQL_CONNECT_FORCE_NEW;
    } else {
      $flags = null;
    }
    return $flags;
  }

  /**
   * Define all PgSQL attibute of the conection a ready exist
   * 
   * @return void
   */
  public static function define(): void
  {
    $version = pg_version(PgSQLEngine::getInstance()->getConnection());
    $result = [];
    foreach (self::$attributeList as $key => $value) {
      $result[self::$attributeList[$key]] = match (self::$attributeList[$key]) {
        'AUTOCOMMIT' => '0',  
        'ERRMODE' => (string) '1',
        'CASE' => '0',
        'CLIENT_VERSION' => $version['client'],
        'CONNECTION_STATUS' => (pg_connection_status(PgSQLEngine::getInstance()->getConnection()) === PGSQL_CONNECTION_OK) ? 'Connection OK; waiting to send.' : 'Connection failed;',
        'PERSISTENT' => (int) !Options::getOptions(PgSQL::ATTR_PERSISTENT) ? 0 : (int) Options::getOptions(PgSQL::ATTR_PERSISTENT),
        'SERVER_INFO' => sprintf("PID: %s; Client Encoding: %s; Is Superuser: %s; Session Authorization: %s; Date Style: %s", pg_get_pid(PgSQLEngine::getInstance()->getConnection()), pg_client_encoding(PgSQLEngine::getInstance()->getConnection()), $version['is_superuser'], $version['session_authorization'], $version['DateStyle']),
        'SERVER_VERSION' => $version['server'],
        'TIMEOUT' => Options::getOptions(PgSQL::ATTR_CONNECT_TIMEOUT) ? Options::getOptions(PgSQL::ATTR_CONNECT_TIMEOUT) : 30,
        'EMULATE_PREPARES' => 'FAKE',
        'DEFAULT_FETCH_MODE' => (string) '3',
        'CHARACTER_SET' => pg_client_encoding(PgSQLEngine::getInstance()->getConnection()),
        'COLLATION' => pg_client_encoding(PgSQLEngine::getInstance()->getConnection())
      };
    };
    PgSQLEngine::getInstance()?->setAttributes((array) $result);
  }
}
