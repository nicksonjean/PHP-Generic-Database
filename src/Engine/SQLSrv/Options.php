<?php

namespace GenericDatabase\Engine\SQLSrv;

use
  GenericDatabase\Engine\SQLSrvEngine,
  GenericDatabase\Traits\Reflections;

class Options
{
  use Reflections;

  private static $options = [];

  /**
   * This method is responsible for obtain all options already defined by user
   * 
   * @param ?string|null $type
   * @return mixed
   */
  public static function getOptions(?int $type = null): mixed
  {
    if (!is_null($type)) {
      $result = isset(self::$options[$type]) ? self::$options[$type] : null;
    } else {
      $result = self::$options;
    }
    return $result;
  }

  /**
   * This method is responsible for set options before connect in database
   * 
   * @param ?array|null $type
   * @return void
   */
  public static function setOptions(?array $options = null): void
  {
    $class = 'GenericDatabase\Engine\SQLSrv\SQLSrv';
    foreach (Reflections::getClassConstants($class) as $key => $value) {
      $index = array_search($value, array_keys($options));
      if ($index !== false) {
        $key_name = $key !== 'ATTR_PERSISTENT' && $key !== 'ATTR_CONNECT_TIMEOUT' ? str_replace("ATTR", "SQLSrv", $key) : $key;
        SQLSrvEngine::getInstance()->setAttribute("SQLSrv::$key", $options[$value]);
        if ($key !== 'ATTR_PERSISTENT' && $key !== 'ATTR_CONNECT_TIMEOUT') {
          SQLSrvEngine::getInstance()->setOptions(constant($key_name), $options[$value]);
        }
        self::$options[constant("$class::$key")] = $options[$value];
      }
    }
  }

  /**
   * This method is responsible for set options after connect in database
   * 
   * @return void
   */
  public static function define(): void
  {
    foreach (self::getOptions() as $key => $value) {
      switch ($key) {
        case 'ATTR_PERSISTENT':
          if (ini_get('sqlsrv.persistent_timeout ') !== '1') {
            ini_set('sqlsrv.persistent_timeout ', Options::getOptions(SQLSrv::ATTR_CONNECT_TIMEOUT));
          }
          break;
        default:
      }
    }
  }
}
