<?php

namespace GenericDatabase\Engine\SQLite;

use
  GenericDatabase\Traits\Regex,
  GenericDatabase\Traits\JSON,
  GenericDatabase\Traits\INI,
  GenericDatabase\Traits\YAML,
  GenericDatabase\Traits\XML,
  GenericDatabase\Engine\SQLiteEngine;

class Arguments
{
  /**
   * array property for use in magic setter and getter in order
   */
  private static $argumentList = [
    'Database',
    'Charset',
    'Options',
    'Exception'
  ];

  /**
   * This method is used when all parameters are used
   * 
   * @param array $arguments
   * @return void
   */
  private static function callWithFullArguments($arguments): void
  {
    foreach ($arguments as $key => $value) {
      call_user_func_array([SQLiteEngine::getInstance(), 'set' . self::$argumentList[$key]], [$value]);
    }
  }

  /**
   * Transform variables in constants
   *
   * @param array $value
   * @return array 
   */
  private static function setConstant($value): array
  {
    $options = [];
    foreach (array_combine(array_keys(...$value), array_values(...$value)) as $key => $value) {
      $index = str_replace('SQLite::', '', $key);
      $key_name = $index !== 'ATTR_PERSISTENT' && $index !== 'ATTR_AUTOCOMMIT' && $index !== 'ATTR_CONNECT_TIMEOUT' ? str_replace("ATTR", "SQLITE3", $index) : $index;
      SQLiteEngine::getInstance()->setAttribute($key, $value);
      if ($key_name !== 'ATTR_PERSISTENT' && $key_name !== 'ATTR_AUTOCOMMIT' && $key_name !== 'ATTR_CONNECT_TIMEOUT') {
        SQLiteEngine::getInstance()->setOptions(constant($key_name), $value);
      }
      $options[constant("GenericDatabase\Engine\SQLite\SQLite::$index")] = $value;
    }
    Options::setOptions($options);
    $options = Options::getOptions();
    return $options;
  }

  /**
   * Determines the type that will receive treatment
   *
   * @param mixed $value
   * @return mixed
   */
  private static function setType($value): mixed
  {
    $length = strlen($value);
    $value = ($value === null) ? '' : $value;
    if (Regex::isNumber($value) && $length > 1) {
      $result = (int) $value;
    } else if (($value === '0' or $value === '1') && $length === 1) {
      $result = (bool) $value;
    } else if (Regex::isBoolean($value)) {
      $result = (bool) $value;
    } else {
      $result = (string) $value;
    }
    return $result;
  }

  /**
   * Determines arguments type by calling to format type
   *
   * @param string $format Accept formats json, xml, ini and yaml
   * @param mixed $arguments
   * @return void
   */
  private static function callArgumentsByFormat($format, $arguments): void
  {
    $data = null;
    if ($format === 'json') {
      $data = JSON::parseJSON(...$arguments);
    } elseif ($format === 'ini') {
      $data = INI::parseINI(...$arguments);
    } elseif ($format === 'xml') {
      $data = XML::parseXML(...$arguments);
    } elseif ($format === 'yaml') {
      $data = YAML::parseYAML(...$arguments);
    }

    if ($data) {
      foreach ($data as $key => $value) {
        if (strtolower($key) === 'options') {
          call_user_func_array([SQLiteEngine::getInstance(), 'set' . ucfirst($key)], [self::setConstant(($format === 'json' || $format === 'yaml') ? $value : [$value])]);
        } else {
          call_user_func_array([SQLiteEngine::getInstance(), 'set' . ucfirst($key)], [self::setType($value)]);
        }
      }
    }
  }

  /**
   * Determines arguments type by calling to default type
   *
   * @param mixed $arguments
   * @return void
   */
  private static function callArgumentsByDefault($method, $arguments): void
  {
    call_user_func_array([SQLiteEngine::getInstance(), $method], $arguments);
  }

  /**
   * This method works like a factory and is responsible for identifying the way in which the class is instantiated, as well as its arguments.
   * 
   * @param string $method
   * @param array $arguments
   * @return SQLiteEngine
   */
  public static function call(string $method, array $arguments): mixed
  {
    switch ($method) {
      case 'new':
      case 'create':
      case 'config':
        if (count($arguments) === 4) {
          self::callWithFullArguments($arguments);
        } else {
          if (JSON::isValidJSON(...$arguments)) {
            self::callArgumentsByFormat('json', $arguments);
          } else if (YAML::isValidYAML(...$arguments)) {
            self::callArgumentsByFormat('yaml', $arguments);
          } else if (INI::isValidINI(...$arguments)) {
            self::callArgumentsByFormat('ini', $arguments);
          } else if (XML::isValidXML(...$arguments)) {
            self::callArgumentsByFormat('xml', $arguments);
          }
        }
        break;
      default:
        self::callArgumentsByDefault($method, $arguments);
        break;
    }
    return SQLiteEngine::getInstance();
  }
}