<?php

namespace GenericDatabase\Helpers;

use XMLReader;
use SimpleXMLElement;

class XML
{
    /**
     * Check if xml string is valid
     *
     * @param mixed $xml Argument to be tested
     * @return bool
     */
    public static function isValidXML(mixed $xml): bool
    {
        if (!is_string($xml)) {
            return false;
        }
        set_error_handler(fn () => null, E_WARNING);
        $xml2 = simpleXML_load_file($xml, "SimpleXMLElement", LIBXML_NOCDATA);
        if ($xml2 === false) {
            restore_error_handler();
            return false;
        } else {
            $xml2 = XMLReader::open($xml);
            restore_error_handler();
            return $xml2->setParserProperty(XMLReader::VALIDATE, true) ? true : false;
        }
    }

    /**
     * Convert data to its appropriate type.
     *
     * @param mixed $data The data to convert.
     * @return mixed The converted data.
     */
    public static function convertData($data): mixed
    {
        $data = trim($data);
        if (is_numeric($data)) {
            return strpos($data, '.') !== false ? floatval($data) : intval($data);
        }
        if (strcasecmp($data, 'false') === 0) {
            $data = false;
        } elseif (strcasecmp($data, 'true') === 0) {
            $data = true;
        }
        return $data;
    }

    /**
     * Decode a SimpleXMLElement object into an array or a string.
     *
     * @param SimpleXMLElement $xml The SimpleXMLElement object to decode.
     * @param bool|null $attributesKey Whether to use the 'attributes' key in the array or not.
     * @param bool|null $reduce Whether to reduce the array structure when there is only one child element.
     * @param array|null $alwaysArray An array of element names that should always be treated as arrays.
     * @param array|null $valueKeys The mapping of element names to value keys.
     * @return string|array The decoded XML data as an array or a string.
     */
    public static function decodeXML(
        SimpleXMLElement $xml,
        ?bool $attributesKey = true,
        ?bool $reduce = true,
        ?array $alwaysArray = [],
        ?array $valueKeys = []
    ): string|array {
        $arr = [];
        self::extractAttributes($xml, $attributesKey, $arr);
        $childrenCount = $xml->children()->count();
        if ($childrenCount === 0) {
            self::extractValue($xml, $arr, $valueKeys);
        } else {
            self::processChildren($xml, $arr, $attributesKey, $reduce, $alwaysArray, $valueKeys);
        }
        return $arr;
    }

    /**
     * Extract attributes from a SimpleXMLElement object and add them to an array.
     *
     * @param SimpleXMLElement $xml The SimpleXMLElement object to extract attributes from.
     * @param bool $attributesKey Whether to use the 'attributes' key in the array or not.
     * @param array &$arr The array to add the extracted attributes to.
     * @return void
     */
    private static function extractAttributes(SimpleXMLElement $xml, bool $attributesKey, array &$arr): void
    {
        foreach ($xml->attributes() as $key => $value) {
            $key = strval($key);
            $value = strval($value);
            if ($attributesKey) {
                $arr['attributes'][$key] = $value;
            } else {
                $arr[$key] = $value;
            }
        }
    }

    /**
     * Extract the value from a SimpleXMLElement object and add it to an array.
     *
     * @param SimpleXMLElement $xml The SimpleXMLElement object to extract the value from.
     * @param array &$arr The array to add the extracted value to.
     * @param array $valueKeys The mapping of element names to value keys.
     * @return void
     */
    private static function extractValue(SimpleXMLElement $xml, array &$arr, array $valueKeys): void
    {
        if (!empty($arr)) {
            $key = $valueKeys[$xml->getName()] ?? $valueKeys['*'] ?? "value";
            $arr[$key] = strval($xml);
        } else {
            $arr = strval($xml);
        }
    }

    /**
     * Process the children of a SimpleXMLElement object and add them to an array.
     *
     * @param SimpleXMLElement $xml The SimpleXMLElement object to process the children of.
     * @param array &$arr The array to add the processed children to.
     * @param bool $attributesKey Whether to use the 'attributes' key in the array or not.
     * @param bool $reduce Whether to reduce the array structure when there is only one child element.
     * @param array $alwaysArray An array of element names that should always be treated as arrays.
     * @param array $valueKeys The mapping of element names to value keys.
     * @return void
     */
    private static function processChildren(
        SimpleXMLElement $xml,
        array &$arr,
        bool $attributesKey,
        bool $reduce,
        array $alwaysArray,
        array $valueKeys
    ): void {
        $childrenNames = [];
        foreach ($xml->children() as $child) {
            $childName = $child->getName();
            if (!in_array($childName, $childrenNames, true)) {
                $childrenNames[] = $childName;
            }
        }
        $reducible = empty($arr) && count($childrenNames) === 1;
        foreach ($xml->children() as $child) {
            $name = $child->getName();
            if ($xml->$name->count() > 1 || in_array($name, $alwaysArray, true)) {
                if ($reduce && $reducible) {
                    $arr[] = self::decodeXML($child, $attributesKey, $reduce, $alwaysArray, $valueKeys);
                } else {
                    $arr[$name][] = self::decodeXML($child, $attributesKey, $reduce, $alwaysArray, $valueKeys);
                }
            } else {
                $arr[$name] = self::decodeXML($child, $attributesKey, $reduce, $alwaysArray, $valueKeys);
            }
        }
    }

    /**
     * Parse XML data and convert it into an array.
     *
     * @param string $xml The XML data to parse.
     * @return string|array The parsed XML data as an array or a string.
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
