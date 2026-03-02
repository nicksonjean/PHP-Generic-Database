<?php

namespace GenericDatabase\Helpers\Converters\Writers;

/**
 * Converts a DSN record array to NEON format.
 *
 * Scalar values are written as "key: value" pairs. Numeric values are written
 * unquoted. Values containing '::' (PHP class constants used as DSN keys)
 * are double-quoted. The 'options' field is expanded as a nested NEON block
 * with two-space indentation.
 */
class NeonWriter extends BaseWriter
{
    public function convert(array $data): string
    {
        $lines = [];

        foreach ($data as $k => $v) {
            if ($k === 'options' && is_array($v)) {
                $lines[] = 'options:';
                $opt     = $v[0] ?? $v;
                if (is_array($opt)) {
                    foreach ($opt as $ok => $ov) {
                        $oval    = is_bool($ov) ? ($ov ? 'true' : 'false') : $ov;
                        $lines[] = '  ' . $ok . ': ' . (
                            is_numeric($oval)
                                ? $oval
                                : (str_contains((string) $oval, '::') ? '"' . $oval . '"' : $oval)
                        );
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
        return 'neon';
    }

    public function getFormat(): string
    {
        return 'neon';
    }
}
