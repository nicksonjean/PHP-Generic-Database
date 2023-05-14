<?php

namespace GenericDatabase\Engine\OCI;

use
  GenericDatabase\Engine\OCIEngine,
  GenericDatabase\Engine\OCI\Options;

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

  private static function settings()
  {
    $server_info = oci_server_version(OCIEngine::getInstance()->getConnection());
    $version = preg_replace('~^.* (\d+\.\d+\.\d+\.\d+\.\d+).*~s', '\1', $server_info);
    return [
      'server_info' => $server_info,
      'client_version' => oci_client_version(),
      'server_version' => $version
    ];
  }

  /**
   * Define all OCI attibute of the conection a ready exist
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
        'CLIENT_VERSION' => $settings['client_version'],
        'CONNECTION_STATUS' => OCIEngine::getInstance()->getConnection() ? 'Connection OK; waiting to send.' : 'Connection failed;',
        'PERSISTENT' => (int) !Options::getOptions(OCI::ATTR_PERSISTENT) ? 0 : (int) Options::getOptions(OCI::ATTR_PERSISTENT),
        'SERVER_INFO' => $settings['server_info'],
        'SERVER_VERSION' => $settings['server_version'],
        'TIMEOUT' =>  (int) Options::getOptions(OCI::ATTR_CONNECT_TIMEOUT) ? Options::getOptions(OCI::ATTR_CONNECT_TIMEOUT) : 30,
        'EMULATE_PREPARES' => 'FAKE',
        'DEFAULT_FETCH_MODE' => (int) 3,
        'CHARACTER_SET' => OCIEngine::getInstance()?->getCharset(),
        'COLLATION' => OCIEngine::getInstance()?->getCharset() === 'utf8' ? 'unicode_ci_ai' : 'none',
      };
    };
    OCIEngine::getInstance()?->setAttributes((array) $result);
  }
}
