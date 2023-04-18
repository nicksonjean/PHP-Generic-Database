<?php

namespace GenericDatabase\Engine\FBird;

use
  GenericDatabase\Engine\FBirdEngine,
  GenericDatabase\Engine\FBird\Options;

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
    if (($service = ibase_service_attach(FBirdEngine::getInstance()->getHost() . '/' . FBirdEngine::getInstance()->getPort(), FBirdEngine::getInstance()->getUser(), FBirdEngine::getInstance()->getPassword())) != FALSE) {
      preg_match('/information:\s(.*)\sVariable/s', ibase_db_info($service, FBirdEngine::getInstance()->getDatabase(), 4), $matches, PREG_OFFSET_CAPTURE, 0);
      $results = [];
      foreach (preg_split("/((\r?\n)|(\r\n?))/", trim(preg_replace('/\t((?:[A-Za-z]+\s){0,2}[A-Za-z]+)\t+(.*)/m', "$1| $2", $matches[1][0]))) as $lines) {
        $lines = trim($lines);
        if (strlen($lines) > 0) {
          foreach (explode('|', $lines) as $key => $line) {
            if ($key === 0) {
              $name = str_replace(' ', '_', strtolower(trim($line)));
            }
            if ($key === 1) {
              $value = trim($line);
            }
            $results[$name] = $value ?? null;
          }
        }
      }

      $server = vsprintf('%s %s %s on disk structure version %s ', [
        ibase_server_info($service, IBASE_SVC_IMPLEMENTATION) . ' (access method), version "' . ibase_server_info($service, IBASE_SVC_SERVER_VERSION) . '"',
        ibase_server_info($service, IBASE_SVC_IMPLEMENTATION) . ' (remote method), version "' . ibase_server_info($service, IBASE_SVC_SERVER_VERSION) . '/tcp (' . gethostname() . ')/P15:C"',
        ibase_server_info($service, IBASE_SVC_IMPLEMENTATION) . ' (remote interface), version "' . ibase_server_info($service, IBASE_SVC_SERVER_VERSION) . '/tcp (' . gethostname() . ')/P15:C"',
        $results['ods_version']
      ]);


      ibase_service_detach($service);

      return [
        ...$results,
        'server_version' => ibase_server_info($service, IBASE_SVC_SERVER_VERSION),
        'server_implementation' => ibase_server_info($service, IBASE_SVC_IMPLEMENTATION),
        'server_users' => ibase_server_info($service, IBASE_SVC_GET_USERS),
        'server_directory' => ibase_server_info($service, IBASE_SVC_GET_ENV),
        'server_lock_path' => ibase_server_info($service, IBASE_SVC_GET_ENV_LOCK),
        'server_lib_path' => ibase_server_info($service, IBASE_SVC_GET_ENV_MSG),
        'user_database_path' => ibase_server_info($service, IBASE_SVC_USER_DBPATH),
        'database_info' => ibase_server_info($service, IBASE_SVC_SVR_DB_INFO),
        'server_info' => $server
      ];
    }
  }

  /**
   * Define all FBird attibute of the conection a ready exist
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
        'CLIENT_VERSION' => $settings['server_version'],
        'CONNECTION_STATUS' => FBirdEngine::getInstance()->getConnection() ? 'Connection OK; waiting to send.' : 'Connection failed;',
        'PERSISTENT' => (int) !Options::getOptions(FBird::ATTR_PERSISTENT) ? 0 : (int) Options::getOptions(FBird::ATTR_PERSISTENT),
        'SERVER_INFO' => $settings['server_info'],
        'SERVER_VERSION' => $settings['server_info'],
        'TIMEOUT' =>  (int) Options::getOptions(FBird::ATTR_CONNECT_TIMEOUT) ? Options::getOptions(FBird::ATTR_CONNECT_TIMEOUT) : 30,
        'EMULATE_PREPARES' => 'FAKE',
        'DEFAULT_FETCH_MODE' => (int) 3,
        'CHARACTER_SET' => FBirdEngine::getInstance()?->getCharset(),
        'COLLATION' => FBirdEngine::getInstance()?->getCharset() === 'utf8' ? 'unicode_ci_ai' : 'none',
      };
    };
    FBirdEngine::getInstance()?->setAttributes((array) $result);
  }
}
