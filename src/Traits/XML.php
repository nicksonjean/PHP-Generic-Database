<?php

namespace GenericDatabase\Traits;

trait XML
{
    /**
     * Check if xml string is valid
     *
     * @param string $xml Argument to be tested
     * @return bool
     */
    public static function isValidXML(string $xml): bool
    {
        set_error_handler(fn () => null, E_WARNING);
        $xml2 = simpleXML_load_file($xml, "SimpleXMLElement", LIBXML_NOCDATA);
        if ($xml2 === false) {
            restore_error_handler();
            return false;
        } else {
            $xml2 = \XMLReader::open($xml);
            restore_error_handler();
            return ($xml2->setParserProperty(\XMLReader::VALIDATE, true) ? true : false);
        }
    }

    /**
     * Convert a data by type
     *
     * @param mixed $data Argument to be converted
     * @return mixed
     */
    public static function convertData($data): mixed
    {
        $data = trim($data);

        if (preg_match('/^[0-9]{1,}$/', $data)) {
            return intval($data);
        }

        if (preg_match('/^[0-9\.]{1,}$/', $data)) {
            return floatval($data);
        }

        if (preg_match('/(false|true)/i', $data)) {
            return filter_var($data, FILTER_VALIDATE_BOOLEAN);
        }

        return $data;
    }

    /**
     * Decode a valid xml object
     *
     * @param \SimpleXMLElement $xml valid object SimpleXMLElement
     * @param ?bool $attributes_key = true Optional argument to get attribute key
     * @param ?bool $reduce = true  Optional argumento to make reduce
     * @param ?array $always_array = array() Optional argument to always return a array
     * @param ?array $value_keys = array() Optional argument to get array from values and keys
     * @return string|array
     */
    public static function decodeXML(\SimpleXMLElement $xml, ?bool $attributes_key = true, ?bool $reduce = true, ?array $always_array = array(), ?array $value_keys = array()): string|array
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

    /**
     * Parse a valid xml string
     *
     * @param string $xml Argument to be parsed
     * @return string|array
     */
    public static function parseXML(string $xml): string|array
    {
        libxml_use_internal_errors(true);
        $objXML = simplexml_load_file($xml);
        $result = self::decodeXML($objXML);
        $options = [];
        foreach ($objXML->xpath('//options/option') as $value) {
            $options[((string) $value->attributes()->name)] = self::convertData((string) $value);
        }
        $options = array('options' => $options);
        $result['options'] = $options['options'];
        unset($options['options']);
        return $result;
    }
}
