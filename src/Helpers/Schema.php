<?php

namespace GenericDatabase\Helpers;

class Schema
{
    private static string $folderPath;
    private static string $separator = ';';

    private static function allColumnsNull($data): bool
    {
        foreach ($data as $row) {
            foreach ($row as $value) {
                if ($value !== null) {
                    return false;
                }
            }
        }
        return true;
    }

    public static function parse($filePath): array
    {
        $data = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            $header = fgetcsv($handle, 0, self::$separator);
            if (($row = fgetcsv($handle, 0, self::$separator)) !== false) {
                while ($row !== false) {
                    $rowData = [];
                    foreach ($header as $index => $columnName) {
                        $rowData[$columnName] = $row[$index] ?? null;
                    }
                    $data[] = $rowData;
                    $row = fgetcsv($handle, 0, self::$separator);
                }
            } else {
                foreach ($header as $columnName) {
                    $data[] = [$columnName => null];
                }
            }
            fclose($handle);
        }
        return $data;
    }

    private static function analyze($data): array
    {
        $types = [];
        if (self::allColumnsNull($data)) {
            foreach ($data as $value) {
                $types[array_keys($value)[0]] = 'string';
            }
            return $types;
        }
        foreach ($data[0] as $columnName => $value) {
            $types[$columnName] = 'string';
        }
        foreach ($data as $row) {
            foreach ($row as $columnName => $value) {
                if (is_numeric($value) && (int) $value == $value) {
                    if ($types[$columnName] != 'float') {
                        $types[$columnName] = 'integer';
                    }
                } elseif (is_float($value)) {
                    $types[$columnName] = 'float';
                } elseif (strtotime($value) !== false && preg_match('/[^a-zA-Z\s{1,}]/', $value)) {
                    $types[$columnName] = 'datetime';
                }
            }
        }
        return $types;
    }

    public static function structure($filePath, $separator): array
    {
        self::$folderPath = $filePath;
        self::$separator = $separator;
        $files = glob(self::$folderPath . '\*.csv');
        $schemas = [];
        foreach ($files as $file) {
            $data = self::parse($file);
            $types = self::analyze($data);
            $schema = [];
            foreach ($types as $columnName => $type) {
                $schema[] = ['name' => $columnName, 'type' => $type];
            }
            $schemas[basename($file)] = $schema;
        }
        return $schemas;
    }

    public static function write($overwrite = false): void
    {
        $schemaFilePath = self::$folderPath . '\Schema.ini';
        if (!file_exists($schemaFilePath) || $overwrite) {
            $structure = self::structure(self::$folderPath, self::$separator);
            $output = '';
            foreach ($structure as $filename => $columns) {
                $output .= "[$filename]\n";
                $output .= "Format=Delimited(;) \n";
                $output .= "ColNameHeader=True\n";
                foreach ($columns as $index => $column) {
                    $regexParts = [
                        "/([\x{00}-\x{7E}]|",
                        "[\x{C2}-\x{DF}][\x{80}-\x{BF}]|",
                        "\x{E0}[\x{A0}-\x{BF}][\x{80}-\x{BF}]|",
                        "[\x{E1}-\x{EC}\x{EE}\{xEF}][\x{80}-\x{BF}]{2}|",
                        "\x{ED}[\x{80}-\x{9F}][\x{80}-\x{BF}]|",
                        "\x{F0}[\x{90}-\x{BF}][\x{80}-\x{BF}]{2}|",
                        "[\x{F1}-\x{F3}][\x{80}-\x{BF}]{3}|",
                        "\x{F4}[\x{80}-\x{8F}][\x{80}-\x{BF}]{2})|",
                        "(.)/s"
                    ];
                    $columnName = preg_replace(implode('', $regexParts), "$1", $column['name']);
                    $output .= 'Col' . ($index + 1) . '="' . $columnName . '"';
                    $output .= match ($column['type']) {
                        'integer' => " Integer\n",
                        'date' => " Date\n",
                        'datetime' => " DateTime\n",
                        default => " Text\n",
                    };
                }
                $output .= "MaxScanRows=0\n";
                $output .= "CharacterSet=ANSI\n";
                $output .= "DateTimeFormat=yyyy-MM-dd HH:nn:ss\n";
            }
            file_put_contents($schemaFilePath, $output);
        }
    }
}
