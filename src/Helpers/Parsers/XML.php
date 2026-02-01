<?php

namespace GenericDatabase\Helpers\Parsers;

use XMLReader;
use SimpleXMLElement;

/**
 * The `GenericDatabase\Helpers\Parsers\XML` class provides a set of static methods for working with XML data.
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
 * - `isValidXML($xml)`: Checks if an XML string is valid by loading it as a `SimpleXMLElement` object and using `XMLReader` to validate it.
 * - `convertData($data)`: Converts data to appropriate types, such as integers, floats, booleans, or leaves it as a string.
 * - `decodeXML($xml, $attributesKey, $reduce, $alwaysArray, $valueKeys)`: Decodes a `SimpleXMLElement` object into an array or a string. It extracts attributes, values, and children elements recursively.
 * - `extractAttributes($xml, $attributesKey, &$arr)`: Extracts attributes from a `SimpleXMLElement` object and adds them to an array.
 * - `extractValue($xml, &$arr, $valueKeys)`: Extracts the value from a `SimpleXMLElement` object and adds it to an array.
 * - `processChildren($xml, &$arr, $attributesKey, $reduce, $alwaysArray, $valueKeys)`: Processes the children of a `SimpleXMLElement` object and adds them to an array.
 * - `parseXML($xml)`: Parses XML data into an array. It uses decodeXML to decode the XML and extract options as a separate array.
 *
 * @package GenericDatabase\Helpers\Parsers
 * @subpackage XML
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

        // Check if it's a directory - directories cannot be valid XML
        if (is_dir($xml)) {
            return false;
        }

        set_error_handler(fn(): bool => true, E_WARNING);
        $xmlData = simpleXML_load_file($xml, "SimpleXMLElement", LIBXML_NOCDATA);
        if ($xmlData === false) {
            restore_error_handler();
            $result = false;
        } else {
            $xmlData = new XMLReader();
            $xmlData->open($xml);
            restore_error_handler();
            $result = $xmlData->setParserProperty(XMLReader::VALIDATE, true);
        }
        return $result;
    }

    /**
     * Convert data to its appropriate type.
     *
     * @param mixed $data The data to convert.
     * @return string|int|bool|float The converted data.
     */
    private static function convertData(mixed $data): string|int|bool|float
    {
        $data = trim((string) $data);
        if (is_numeric($data)) {
            return str_contains($data, '.') ? floatval($data) : intval($data);
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
        // Check if it's a directory - directories cannot be parsed as XML
        if (is_dir($xml)) {
            return [];
        }

        libxml_use_internal_errors(true);
        $objXML = simplexml_load_file($xml);
        $result = self::decodeXML($objXML);
        $options = [];
        foreach ($objXML->xpath('//options/option') as $value) {
            $options[((string) $value->attributes()->name)] = self::convertData((string) $value);
        }
        $options = ['options' => $options];
        $result['options'] = $options['options'];
        unset($options['options']);
        libxml_use_internal_errors(false);
        return $result;
    }

    /**
     * Parse XML string content (tabular format with root/item structure) into array of rows.
     * Expects structure: <root><item><col1>val</col1><col2>val</col2></item>...</root>
     *
     * @param string $content The XML string content.
     * @return array Array of rows (associative arrays).
     */
    public static function parseTableXmlString(string $content): array
    {
        $content = trim($content);
        if ($content === '') {
            return [];
        }

        libxml_use_internal_errors(true);
        $xml = @simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
        libxml_use_internal_errors(false);

        if ($xml === false) {
            return [];
        }

        $rows = [];
        foreach ($xml->item ?? [] as $item) {
            $decoded = json_decode(json_encode($item), true);
            if (!is_array($decoded)) {
                continue;
            }
            $row = [];
            foreach ($decoded as $key => $value) {
                if ($key === '@attributes') {
                    continue;
                }
                $row[$key] = is_array($value) ? ($value[0] ?? '') : (string) $value;
            }
            if (!empty($row)) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    /**
     * Encode array of rows to XML format (root/item structure).
     *
     * @param array $data Array of rows (associative arrays).
     * @param int $flags 0 = compact, 1 = pretty (indented).
     * @return string XML format string.
     */
    public static function encodeTableToXml(array $data, int $flags = 0): string
    {
        $indent = $flags === 1 ? '  ' : '';
        $nl = $flags === 1 ? "\n" : '';
        $decl = '<?xml version="1.0" encoding="UTF-8"?>' . $nl;
        $decl .= '<root>' . $nl;

        foreach ($data as $row) {
            if (!is_array($row) && !$row instanceof \stdClass) {
                continue;
            }
            $decl .= $indent . '<item>' . $nl;
            foreach ((array) $row as $key => $value) {
                $val = htmlspecialchars((string) ($value ?? ''), ENT_XML1, 'UTF-8');
                $decl .= str_repeat($indent, 2) . '<' . $key . '>' . $val . '</' . $key . '>' . $nl;
            }
            $decl .= $indent . '</item>' . $nl;
        }
        $decl .= '</root>';
        return $decl;
    }
}
