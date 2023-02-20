<?php

namespace GenericDatabase;

use Getter, Setter, Transporter, Arrays, Regex, JSON, INI, YAML, XML;

class PDOArguments
{
  use Getter, Setter, Transporter;
  /**
   * array property for use in magic setter and getter in order
   */
  private static $argumentList = [
    'Driver',
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
      call_user_func_array([PDOEngine::getInstance(), 'set' . self::$argumentList[$key]], [$value]);
    }
  }

  /**
   * Execute set<argumentList> functions on PDOEngine with given arguments 
   *
   * @param mixed $method
   * @param mixed $arguments
   * @return void
   */
  private static function callWithPartialArguments($arguments): void
  {
    $clonedArgumentList = Arrays::exceptByValues(self::$argumentList, ['Host', 'Port', 'User', 'Password']);

    foreach ($arguments as $key => $value) {
      call_user_func_array([PDOEngine::getInstance(), 'set' . $clonedArgumentList[$key]], [$value]);
    }
  }

  /**
   * Transform variables in constants
   *
   * @param mixed $value
   * @param mixed $type
   * @return array 
   */
  private static function setConstant($value, $type): array
  {
    $result = [];
    foreach (array_combine(array_keys(...$value), array_values(...$value)) as $k => $v) {
      if ($type) {
        $length = strlen($v);
        if (Regex::isNumber($v) && $length > 1) {
          $result[constant($k)] = (int) $v;
        } else if (($v === '0' or $v === '1') && $length === 1) {
          $result[constant($k)] = (bool) $v;
        } else {
          $result[constant($k)] = constant($v);
        }
      } else {
        if (Regex::isNumber($v) && !Regex::isBoolean($v)) {
          $result[constant($k)] = (int) $v;
        } else if (Regex::isBoolean($v)) {
          $result[constant($k)] = (bool) $v;
        } else {
          $result[constant($k)] = constant($v);
        }
      }
    }
    return $result;
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
        call_user_func_array([PDOEngine::getInstance(), 'set' . ucfirst($key)], [self::setConstant($value, false)]);
      } else {
        call_user_func_array([PDOEngine::getInstance(), 'set' . ucfirst($key)], [self::setType($value)]);
      }
    }
  }

  private static function callArgumentsByINI($arguments): void
  {
    foreach (INI::parseINI(...$arguments) as $key => $value) {
      if ($key == 'options') {
        call_user_func_array([PDOEngine::getInstance(), 'set' . ucfirst($key)], [self::setConstant([$value], true)]);
      } else {
        call_user_func_array([PDOEngine::getInstance(), 'set' . ucfirst($key)], [self::setType($value)]);
      }
    }
  }

  private static function callArgumentsByYAML($arguments): void
  {
    foreach (YAML::parseYAML(...$arguments) as $key => $value) {
      if ($key == 'options') {
        call_user_func_array([PDOEngine::getInstance(), 'set' . ucfirst($key)], [self::setConstant($value, false)]);
      } else {
        call_user_func_array([PDOEngine::getInstance(), 'set' . ucfirst($key)], [self::setType($value)]);
      }
    }
  }

  private static function callArgumentsByXML($arguments): void
  {
    foreach (XML::parseXML(...$arguments) as $key => $value) {
      if ($key == 'options') {
        call_user_func_array([PDOEngine::getInstance(), 'set' . ucfirst($key)], [self::setConstant([$value], true)]);
      } else {
        call_user_func_array([PDOEngine::getInstance(), 'set' . ucfirst($key)], [self::setType($value)]);
      }
    }
  }

  private static function callArgumentsByDefault($method, $arguments): void
  {
    call_user_func_array([PDOEngine::getInstance(), $method], $arguments);
  }

  public static function call(string $method, array $arguments): mixed
  {
    switch ($method) {
      case 'new':
      case 'create':
      case 'config':
        if (count($arguments) === 9) {
          self::callWithFullArguments($arguments);
        } else if (count($arguments) === 5) {
          self::callWithPartialArguments($arguments);
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
    return PDOEngine::getInstance();
  }
}
