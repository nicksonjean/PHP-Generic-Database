<?php

namespace GenericDatabase\Engine\SQLite3;

use
  GenericDatabase\Traits\Regex,
  GenericDatabase\Traits\JSON,
  GenericDatabase\Traits\INI,
  GenericDatabase\Traits\YAML,
  GenericDatabase\Traits\XML,
  GenericDatabase\Engine\SQLite3Engine;

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
      call_user_func_array([SQLite3Engine::getInstance(), 'set' . self::$argumentList[$key]], [$value]);
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
      SQLite3Engine::getInstance()->setAttribute($key, $value);
      if ($key_name !== 'ATTR_PERSISTENT' && $key_name !== 'ATTR_AUTOCOMMIT' && $key_name !== 'ATTR_CONNECT_TIMEOUT') {
        SQLite3Engine::getInstance()->setOptions(constant($key_name), $value);
      }
      $options[constant("GenericDatabase\Engine\SQLite3\SQLite::$index")] = $value;
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
   * Determines arguments type by calling to JSON type
   *
   * @param mixed $arguments
   * @return void
   */

  private static function callArgumentsByJSON($arguments): void
  {
    foreach (JSON::parseJSON(...$arguments) as $key => $value) {
      if ($key == 'options') {
        call_user_func_array([SQLite3Engine::getInstance(), 'set' . ucfirst($key)], [self::setConstant($value)]);
      } else {
        call_user_func_array([SQLite3Engine::getInstance(), 'set' . ucfirst($key)], [self::setType($value)]);
      }
    }
  }

  /**
   * Determines arguments type by calling to INI type
   *
   * @param mixed $arguments
   * @return void
   */
  private static function callArgumentsByINI($arguments): void
  {
    foreach (INI::parseINI(...$arguments) as $key => $value) {
      if ($key == 'options') {
        call_user_func_array([SQLite3Engine::getInstance(), 'set' . ucfirst($key)], [self::setConstant([$value])]);
      } else {
        call_user_func_array([SQLite3Engine::getInstance(), 'set' . ucfirst($key)], [self::setType($value)]);
      }
    }
  }

  /**
   * Determines arguments type by calling to YAML type
   *
   * @param mixed $arguments
   * @return void
   */
  private static function callArgumentsByYAML($arguments): void
  {
    foreach (YAML::parseYAML(...$arguments) as $key => $value) {
      if ($key == 'options') {
        call_user_func_array([SQLite3Engine::getInstance(), 'set' . ucfirst($key)], [self::setConstant($value)]);
      } else {
        call_user_func_array([SQLite3Engine::getInstance(), 'set' . ucfirst($key)], [self::setType($value)]);
      }
    }
  }

  /**
   * Determines arguments type by calling to XML type
   *
   * @param mixed $arguments
   * @return void
   */
  private static function callArgumentsByXML($arguments): void
  {
    foreach (XML::parseXML(...$arguments) as $key => $value) {
      if ($key == 'options') {
        call_user_func_array([SQLite3Engine::getInstance(), 'set' . ucfirst($key)], [self::setConstant([$value])]);
      } else {
        call_user_func_array([SQLite3Engine::getInstance(), 'set' . ucfirst($key)], [self::setType($value)]);
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
    call_user_func_array([SQLite3Engine::getInstance(), $method], $arguments);
  }

  /**
   * This method works like a factory and is responsible for identifying the way in which the class is instantiated, as well as its arguments.
   * 
   * @param string $method
   * @param array $arguments
   * @return SQLite3Engine
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
            self::callArgumentsByJSON($arguments);
          } else if (
            YAML::isValidYAML(...$arguments)
          ) {
            self::callArgumentsByYAML($arguments);
          } else if (
            INI::isValidINI(...$arguments)
          ) {
            self::callArgumentsByINI($arguments);
          } else if (XML::isValidXML(...$arguments)) {
            self::callArgumentsByXML($arguments);
          }
        }
        break;
      default:
        self::callArgumentsByDefault($method, $arguments);
        break;
    }
    return SQLite3Engine::getInstance();
  }
}
