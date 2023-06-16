<?php

namespace GenericDatabase\Engine\SQLite3;

use
  GenericDatabase\Engine\SQLite3Engine,
  GenericDatabase\Engine\SQLite3\Options;

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
    'DEFAULT_FETCH_MODE'
  ];

  private static function settings()
  {
    $version = \SQLite3::version();
    return [
      'versionString' => $version['versionString'],
      'versionNumber' => $version['versionNumber']
    ];
  }

  /**
   * Define all SQLite3 attibute of the conection a ready exist
   * 
   * @return void
   */
  public static function define(): void
  {

    $settings = self::settings();
    $result = [];
    foreach (self::$attributeList as $key => $value) {
      $result[self::$attributeList[$key]] = match (self::$attributeList[$key]) {
        'AUTOCOMMIT' => (int) 0,
        'ERRMODE' => (int) 1,
        'CASE' => (int) 0,
        'CLIENT_VERSION' => $settings['versionString'],
        'CONNECTION_STATUS' => SQLite3Engine::getInstance()->getConnection() ? 'Connection OK; waiting to send.' : 'Connection failed;',
        'PERSISTENT' => (int) !Options::getOptions(SQLite::ATTR_PERSISTENT) ? 0 : (int) Options::getOptions(SQLite::ATTR_PERSISTENT),
        'SERVER_INFO' => '',
        'SERVER_VERSION' => $settings['versionNumber'],
        'TIMEOUT' =>  (int) Options::getOptions(SQLite::ATTR_CONNECT_TIMEOUT) ? Options::getOptions(SQLite::ATTR_CONNECT_TIMEOUT) : 30,
        'EMULATE_PREPARES' => 'FAKE',
        'DEFAULT_FETCH_MODE' => (int) 3
      };
    };
    SQLite3Engine::getInstance()?->setAttributes((array) $result);
  }
}
