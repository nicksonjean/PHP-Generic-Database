<?php

namespace GenericDatabase\Engine\MySQLi;

use
  GenericDatabase\Traits\Regex,
  GenericDatabase\Traits\JSON,
  GenericDatabase\Traits\INI,
  GenericDatabase\Traits\YAML,
  GenericDatabase\Traits\XML,
  GenericDatabase\Engine\MySQli\MySQL,
  GenericDatabase\Engine\MySQLiEngine;

class Arguments
{
  /**
   * array property for use in magic setter and getter in order
   */
  private static $argumentList = [
    'Host',
    'Port',
    'Database',
    'User',
    'Password',
    'Charset',
    'Options',
    'Exception'
  ];

  /**
   * Call the function callWithFullArguments with the supplied argument
   * 
   * @param mixed $arguments
   * @return void
   */
  private static function callWithFullArguments($arguments): void
  {
    foreach ($arguments as $key => $value) {
      call_user_func_array([MySQLiEngine::getInstance(), 'set' . self::$argumentList[$key]], [$value]);
    }
  }

  /**
   * Transform variables in constants
   *
   * @param mixed $value
   * @param mixed $type
   * @return array 
   */
  private static function setConstant($value): array
  {
    Options::setOptions(Options::fixOptions(...$value));
    $options = [];
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

  private static function callArgumentsByJSON($arguments): void
  {
    foreach (JSON::parseJSON(...$arguments) as $key => $value) {
      if ($key == 'options') {
        call_user_func_array([MySQLiEngine::getInstance(), 'set' . ucfirst($key)], [self::setConstant($value)]);
      } else {
        call_user_func_array([MySQLiEngine::getInstance(), 'set' . ucfirst($key)], [self::setType($value)]);
      }
    }
  }

  private static function callArgumentsByINI($arguments): void
  {
    foreach (INI::parseINI(...$arguments) as $key => $value) {
      if ($key == 'options') {
        call_user_func_array([MySQLiEngine::getInstance(), 'set' . ucfirst($key)], [self::setConstant([$value])]);
      } else {
        call_user_func_array([MySQLiEngine::getInstance(), 'set' . ucfirst($key)], [self::setType($value)]);
      }
    }
  }

  private static function callArgumentsByYAML($arguments): void
  {
    foreach (YAML::parseYAML(...$arguments) as $key => $value) {
      if ($key == 'options') {
        call_user_func_array([MySQLiEngine::getInstance(), 'set' . ucfirst($key)], [self::setConstant($value)]);
      } else {
        call_user_func_array([MySQLiEngine::getInstance(), 'set' . ucfirst($key)], [self::setType($value)]);
      }
    }
  }

  private static function callArgumentsByXML($arguments): void
  {
    foreach (XML::parseXML(...$arguments) as $key => $value) {
      if ($key == 'options') {
        call_user_func_array([MySQLiEngine::getInstance(), 'set' . ucfirst($key)], [self::setConstant([$value])]);
      } else {
        call_user_func_array([MySQLiEngine::getInstance(), 'set' . ucfirst($key)], [self::setType($value)]);
      }
    }
  }

  private static function callArgumentsByDefault($method, $arguments): void
  {
    call_user_func_array([MySQLiEngine::getInstance(), $method], $arguments);
  }

  public static function call(string $method, array $arguments): mixed
  {
    switch ($method) {
      case 'new':
      case 'create':
      case 'config':
        if (count($arguments) === 8) {
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
    return MySQLiEngine::getInstance();
  }
}
