<?php

namespace GenericDatabase\Engine\MySQLi;

use
  GenericDatabase\Engine\MySQLiEngine,
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
    $class = 'GenericDatabase\Engine\MySQli\MySQL';
    foreach (Reflections::getClassConstants($class) as $key => $value) {
      $index = array_search($value, array_keys($options));
      if ($index !== false) {
        $key_name = $key !== 'ATTR_PERSISTENT' && $key !== 'ATTR_AUTOCOMMIT' ? str_replace("ATTR", "MYSQLI", $key) : $key;
        MySQLiEngine::getInstance()->setAttribute("MySQL::$key", $options[$value]);
        if ($key !== 'ATTR_PERSISTENT' && $key !== 'ATTR_AUTOCOMMIT') {
          MySQLiEngine::getInstance()->setOptions(constant($key_name), $options[$value]);
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
          if (ini_get('mysqli.allow_persistent') !== '1') {
            ini_set('mysqli.allow_persistent', 1);
          }
          break;
        case 'ATTR_OPT_LOCAL_INFILE':
          if (ini_get('mysqli.allow_local_infile') !== '1') {
            ini_set('mysqli.allow_local_infile', 1);
          }
          break;
        case 'ATTR_INIT_COMMAND':
          MySQLiEngine::getInstance()->getConnection()->query($value);
          break;
        case 'ATTR_SET_CHARSET_NAME':
          MySQLiEngine::getInstance()->getConnection()->set_charset($value);
          break;
        case 'ATTR_OPT_CONNECT_TIMEOUT':
          MySQLiEngine::getInstance()->getConnection()->query("SET GLOBAL connect_timeout=" . $value . "");
          MySQLiEngine::getInstance()->getConnection()->query("SET SESSION interactive_timeout=" . $value . "");
          MySQLiEngine::getInstance()->getConnection()->query("SET SESSION wait_timeout=" . $value . "");
          break;
        case 'ATTR_OPT_READ_TIMEOUT':
          MySQLiEngine::getInstance()->getConnection()->query("SET SESSION net_read_timeout=" . $value . "");
          MySQLiEngine::getInstance()->getConnection()->query("SET SESSION net_write_timeout=" . ($value * 2) . "");
          break;
        default:
          MySQLiEngine::getInstance()->getConnection()->query("SET SESSION sql_mode=''");
          if (MySQLiEngine::getInstance()->getCharset()) {
            MySQLiEngine::getInstance()->getConnection()->set_charset(MySQLiEngine::getInstance()->getCharset());
          }
      }
    }
  }
}
