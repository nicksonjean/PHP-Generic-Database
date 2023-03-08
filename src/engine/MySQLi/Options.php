<?php

namespace GenericDatabase\Engine\MySQLi;

use GenericDatabase\Engine\MySQLiEngine;

class Options
{
  private static $options = [];

  public static function getOptions(?string $type = null)
  {
    return !is_null($type) ? self::$options[$type] : self::$options;
  }

  // pre connect

  public static function fixOptions($cases): array
  {
    $i = 0;
    $result = [];
    foreach (array_combine(array_keys($cases), array_values($cases)) as $key => $value) {
      if ($key !== 'index' && $key !== 'assoc') {
        $index = str_replace('MySQL::', '', $key);
        $result[$i] = [$index => $value];
      }
      $i++;
    }
    return $result;
  }

  // pre connect

  public static function setOptions($cases): void
  {
    $i = 0;
    foreach ($cases as $key => $value) {
      if ($key !== 'index' && $key !== 'assoc') {
        $index = array_keys($cases[$key])[0];
        $value = array_values($value)[0];
        self::$options['index'][$i] = $value;
        self::$options['assoc'][$index] = $value;
        if ($index !== 'ATTR_PERSISTENT' && $index !== 0) {
          MySQLiEngine::getInstance()->setOptions(constant(str_replace("ATTR", "MYSQLI", $index)), $value);
        }
      }
      $i++;
    }
  }

  // post connect

  public static function define(): void
  {
    foreach (self::$options['assoc'] as $key => $value) {
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
      }
    }
  }
}
