<?php

namespace GenericDatabase\Helpers\Converters\Writers;

/**
 * Converts a DSN record array to YAML format.
 *
 * Scalar values are written as "key: value" pairs. Numeric values are written
 * unquoted. Values or keys containing '::' (PHP class constants used as DSN
 * option keys) are double-quoted.
 *
 * The 'options' field is expanded as a YAML block sequence containing a single
 * mapping item, matching the hand-authored convention used in the DSN directory.
 */
class YamlWriter extends BaseWriter
{
    public function convert(array $data): string
    {
        $lines = [];

        foreach ($data as $k => $v) {
            if ($k === 'options' && is_array($v)) {
                $lines[] = 'options:';
                $opt     = $v[0] ?? $v;
                if (is_array($opt)) {
                    $first = true;
                    foreach ($opt as $ok => $ov) {
                        $oval = is_bool($ov) ? ($ov ? 'true' : 'false') : $ov;
                        $key  = str_contains($ok, '::') ? '"' . $ok . '"' : $ok;
                        if ($first) {
                            $lines[] = '  - ' . $key . ': ' . (is_numeric($oval) ? $oval : $oval);
                            $first   = false;
                        } else {
                            $lines[] = '    ' . $key . ': ' . (
                                is_numeric($oval)
                                    ? $oval
                                    : (str_contains((string) $oval, '::') ? '"' . $oval . '"' : $oval)
                            );
                        }
                    }
                }
                continue;
            }

            $val     = is_bool($v) ? ($v ? 'true' : 'false') : $v;
            $lines[] = $k . ': ' . (is_numeric($val) ? $val : (string) $val);
        }

        return implode("\n", $lines) . "\n";
    }

    public function getExtension(): string
    {
        return 'yaml';
    }

    public function getFormat(): string
    {
        return 'yaml';
    }
}
