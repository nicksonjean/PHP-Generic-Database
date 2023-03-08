<?php

namespace GenericDatabase\Traits;

trait XML
{
  public static function isValidXML(string $xml): bool
  {
    $xml = \XMLReader::open($xml);
    return ($xml->setParserProperty(\XMLReader::VALIDATE, true) ? true : false);
  }

  public static function castValue($value)
  {
    $value = trim($value);

    if (preg_match('/^[0-9]{1,}$/', $value)) {
      return intval($value);
    }

    if (preg_match('/^[0-9\.]{1,}$/', $value)) {
      return floatval($value);
    }

    if (preg_match('/(false|true)/i', $value)) {
      return (bool)$value;
    }

    return $value;
  }

  public static function decodeXML(\SimpleXMLElement $xml, bool $attributes_key = true, bool $reduce = true, array $always_array = array(), array $value_keys = array()): string|array
  {
    $arr = array();
    $xml_name = $xml->getName();
    foreach ($xml->attributes() as $key => $value) {
      if ($attributes_key) {
        $arr['attributes'][strval($key)] = strval($value);
      } else {
        $arr[strval($key)] = strval($value);
      }
    }
    $children_count = $xml->children()->count();
    if ($children_count == 0) {
      if (count($arr) > 0) {
        $key = $value_keys[$xml_name] ?? $value_keys['*'] ?? "value";
        $arr[$key] = strval($xml);
      } else {
        $arr = strval($xml);
      }
    } else {
      $children_names = array();
      foreach ($xml->children() as $child) {
        $child_name = $child->getName();
        in_array($child_name, $children_names) or $children_names[] = $child_name;
      }
      $reducible = empty($arr) && count($children_names) === 1;
      foreach ($xml->children() as $child) {
        $name = $child->getName();
        if ($xml->$name->count() > 1 || in_array($name, $always_array)) {
          if ($reduce && $reducible) {
            $arr[] = self::decodeXML($child, $attributes_key, $reduce, $always_array, $value_keys);
          } else {
            $arr[$name][] = self::decodeXML($child, $attributes_key, $reduce, $always_array, $value_keys);
          }
        } else {
          $arr[$name] = self::decodeXML($child, $attributes_key, $reduce, $always_array, $value_keys);
        }
      }
    }
    return $arr;
  }

  public static function parseXML(string $xml)
  {
    libxml_use_internal_errors(TRUE);
    $objXML = simplexml_load_file($xml);
    $result = self::decodeXML($objXML);
    $options = [];
    foreach ($objXML->xpath('//options/option') as $value) {
      $options[((string) $value->attributes()->name)] = self::castValue((string) $value);
    }
    $options = array('options' => $options);
    $result['options'] = $options['options'];
    unset($options['options']);
    return $result;
  }
}
