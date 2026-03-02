<?php

namespace GenericDatabase\Helpers\Converters\Writers;

/**
 * Converts a DSN record array to XML format.
 *
 * The root element is 'root' for records that contain a 'host' key (network-based
 * DSNs such as MySQL, PostgreSQL, etc.) and 'config' for flat-file DSNs that
 * have no host (csv, ini, json, neon, xml, yaml).
 *
 * The 'options' field is expanded as an <options> block containing
 * <option name="key">value</option> child elements, with all values
 * properly escaped for XML.
 */
class XmlWriter extends BaseWriter
{
    public function convert(array $data): string
    {
        $root = array_key_exists('host', $data) ? 'root' : 'config';
        $xml  = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n<{$root}>\n";

        foreach ($data as $k => $v) {
            if ($k === 'options' && is_array($v)) {
                $xml .= "\t<options>\n";
                $opt  = $v[0] ?? $v;
                if (is_array($opt)) {
                    foreach ($opt as $ok => $ov) {
                        $oval = is_bool($ov) ? ($ov ? 'true' : 'false') : $ov;
                        $xml .= "\t\t<option name=\""
                            . htmlspecialchars($ok, ENT_XML1)
                            . "\">"
                            . htmlspecialchars((string) $oval, ENT_XML1)
                            . "</option>\n";
                    }
                }
                $xml .= "\t</options>\n";
                continue;
            }

            $val  = is_bool($v) ? ($v ? 'true' : 'false') : $v;
            $xml .= "\t<{$k}>" . htmlspecialchars((string) $val, ENT_XML1) . "</{$k}>\n";
        }

        $xml .= "</{$root}>\n";

        return $xml;
    }

    public function getExtension(): string
    {
        return 'xml';
    }

    public function getFormat(): string
    {
        return 'xml';
    }
}
