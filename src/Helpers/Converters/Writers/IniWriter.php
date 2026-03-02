<?php

namespace GenericDatabase\Helpers\Converters\Writers;

/**
 * Converts a DSN record array to INI format.
 *
 * Scalar fields are written as key=value pairs. Boolean-like and numeric values
 * are written unquoted. String values are double-quoted with addslashes escaping.
 * The 'options' field expands its entries as options["key"]=value lines.
 */
class IniWriter extends BaseWriter
{
    public function convert(array $data): string
    {
        $boolLiterals = ['true', 'on', 'yes', 'false', 'off', 'no'];
        $iniValue = static function ($v) use ($boolLiterals): string {
            if (is_bool($v)) {
                return $v ? 'true' : 'false';
            }
            $s = (string) $v;
            if (is_numeric($v) || in_array(strtolower($s), $boolLiterals, true)) {
                return $s;
            }
            return '"' . addslashes($s) . '"';
        };

        $lines = [];
        foreach ($data as $k => $v) {
            if ($k === 'options' && is_array($v)) {
                $opt = $v[0] ?? $v;
                if (is_array($opt)) {
                    foreach ($opt as $ok => $ov) {
                        $lines[] = 'options["' . $ok . '"]=' . $iniValue($ov);
                    }
                }
                continue;
            }
            $lines[] = $k . '=' . $iniValue($v);
        }

        return implode("\n", $lines) . "\n";
    }

    public function getExtension(): string
    {
        return 'ini';
    }

    public function getFormat(): string
    {
        return 'ini';
    }
}
