<?php

namespace GenericDatabase\Traits;

trait XML
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
            $xml2 = \XMLReader::open($xml);
            restore_error_handler();
            return $xml2->setParserProperty(\XMLReader::VALIDATE, true) ? true : false;
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
     * Decode a valid xml object
     *
     * @param \SimpleXMLElement $xml valid object SimpleXMLElement
     * @param ?bool $attributesKey = true Optional argument to get attribute key
     * @param ?bool $reduce = true  Optional argumento to make reduce
     * @param ?array $alwaysArray = [] Optional argument to always return a array
     * @param ?array $valueKeys = [] Optional argument to get array from values and keys
     * @return string|array
     */
    public static function decodeXML(
        \SimpleXMLElement $xml,
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
     * extractAttributes a valid xml object
     *
     * @param \SimpleXMLElement $xml valid object SimpleXMLElement
     * @param ?bool $attributesKey = true Optional argument to get attribute key
     * @param array &$arr reference to get array from values and keys
     * @return void
     */
    private static function extractAttributes(\SimpleXMLElement $xml, bool $attributesKey, array &$arr): void
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
     * extractValue a valid xml object
     *
     * @param \SimpleXMLElement $xml valid object SimpleXMLElement
     * @param array &$arr reference to get array from values and keys
     * @param ?array $valueKeys = [] Optional argument to get array from values and keys
     * @return string|array
     */
    private static function extractValue(\SimpleXMLElement $xml, array &$arr, array $valueKeys): void
    {
        if (!empty($arr)) {
            $key = $valueKeys[$xml->getName()] ?? $valueKeys['*'] ?? "value";
            $arr[$key] = strval($xml);
        } else {
            $arr = strval($xml);
        }
    }

    /**
     * processChildren a valid xml object
     *
     * @param \SimpleXMLElement $xml valid object SimpleXMLElement
     * @param ?bool $attributesKey = true Optional argument to get attribute key
     * @param ?bool $reduce = true  Optional argumento to make reduce
     * @param ?array $alwaysArray = [] Optional argument to always return a array
     * @param ?array $valueKeys = [] Optional argument to get array from values and keys
     * @return string|array
     */
    private static function processChildren(
        \SimpleXMLElement $xml,
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
