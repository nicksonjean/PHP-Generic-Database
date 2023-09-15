<?php

namespace GenericDatabase\Helpers;

use XMLReader;
use SimpleXMLElement;

/**
 * The `GenericDatabase\Helpers\XML` class provides a set of static methods for working with XML data.
 * It includes functionalities to check if an XML string is valid, convert data to appropriate types,
 * decode a SimpleXMLElement object into an array or a string, and parse XML data into an array.
 *
 * Example Usage:
 * <code>
 * // Check if an XML string is valid
 * $xmlString = "<root><element>data</element></root>";
 * $isValid = XML::isValidXML($xmlString);
 * </code>
 * `Output: true`
 *
 * <code>
 * // Convert data to appropriate types
 * $data = "123";
 * $convertedData = XML::convertData($data);
 * </code>
 * `Output: 123 (integer)`
 *
 * <code>
 * // Decode a SimpleXMLElement object into an array or a string
 * $xml = simplexml_load_string("<root><element>data</element></root>");
 * $decodedData = XML::decodeXML($xml);
 * </code>
 * `Output: ['element' => 'data']`
 *
 * <code>
 * // Parse XML data into an array
 * $xmlData = "<root><options><option name='option1'>value1</option></options></root>";
 * $parsedData = XML::parseXML($xmlData);
 * </code>
 * `Output: ['options' => ['option1' => 'value1']]`
 *
 * Main functionalities:
 * - Check if an XML string is valid
 * - Convert data to appropriate types
 * - Decode a SimpleXMLElement object into an array or a string
 * - Parse XML data into an array
 *
 * Methods:
 * - `isValidXML($xml)`: Checks if an XML string is valid by loading it as a `SimpleXMLElement` object
 * and using `XMLReader` to validate it.
 * - `convertData($data)`: Converts data to appropriate types, such as integers, floats, booleans,
 * or leaves it as a string.
 * - `decodeXML($xml, $attributesKey, $reduce, $alwaysArray, $valueKeys)`: Decodes a `SimpleXMLElement`
 * object into an array or a string. It extracts attributes, values, and children elements recursively.
 * - `extractAttributes($xml, $attributesKey, &$arr)`: Extracts attributes from a `SimpleXMLElement`
 * object and adds them to an array.
 * - `extractValue($xml, &$arr, $valueKeys)`: Extracts the value from a `SimpleXMLElement` object and
 * adds it to an array.
 * - `processChildren($xml, &$arr, $attributesKey, $reduce, $alwaysArray, $valueKeys)`: Processes the
 * children of a `SimpleXMLElement` object and adds them to an array.
 * - `parseXML($xml)`: Parses XML data into an array. It uses decodeXML to decode the XML and extract
 * options as a separate array.
 *
 * @package GenericDatabase\Helpers
 */
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
        set_error_handler(fn (): bool => true, E_WARNING);
        $lxml = simpleXML_load_file($xml, "SimpleXMLElement", LIBXML_NOCDATA);
        if ($lxml === false) {
            restore_error_handler();
            $result = false;
        } else {
            $lxml = XMLReader::open($xml);
            restore_error_handler();
            $result = $lxml->setParserProperty(XMLReader::VALIDATE, true) ? true : false;
        }
        return $result;
    }

    /**
     * Convert data to its appropriate type.
     *
     * @param mixed $data The data to convert.
     * @return mixed The converted data.
     */
    private static function convertData($data): mixed
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
     */
    private static function decodeXML(SimpleXMLElement $xml): array
    {
        return json_decode(json_encode($xml), true);
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
        libxml_use_internal_errors(false);
        return $result;
    }
}
