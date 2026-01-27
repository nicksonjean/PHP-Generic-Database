<?php

/**
 * Sincroniza DSN de CSV, INI, NEON, XML e YAML com base nos JSON (fonte de verdade).
 * Preenche arquivos vazios e corrige conteúdo incorreto.
 *
 * Uso: php scripts/sync-dsn-from-json.php
 */

declare(strict_types=1);

$baseDir = dirname(__DIR__) . '/resources/dsn';
$jsonDir = $baseDir . '/json';

if (!is_dir($jsonDir)) {
    fwrite(STDERR, "Pasta JSON não encontrada: {$jsonDir}\n");
    exit(1);
}

$formats = [
    'csv'  => ['ext' => 'csv',  'converter' => 'toCsv'],
    'ini'  => ['ext' => 'ini',  'converter' => 'toIni'],
    'neon' => ['ext' => 'neon', 'converter' => 'toNeon'],
    'xml'  => ['ext' => 'xml',  'converter' => 'toXml'],
    'yaml' => ['ext' => 'yaml', 'converter' => 'toYaml'],
];

// CSV/ODBC possui typo: ocbc_* em vez de odbc_*
$csvOdbcRename = static function (string $relPath): string {
    $norm = str_replace('\\', '/', $relPath);
    if (str_contains($norm, 'odbc/odbc_')) {
        return str_replace('odbc_', 'ocbc_', $relPath);
    }
    return $relPath;
};

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($jsonDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

$updated = 0;
$errors  = 0;

foreach ($iterator as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'json') {
        continue;
    }

    $jsonPath = $file->getPathname();
    $relPath  = substr($jsonPath, strlen($jsonDir) + 1);
    $relDir   = dirname($relPath);
    $base     = pathinfo($relPath, PATHINFO_FILENAME);

    $content = @file_get_contents($jsonPath);
    if ($content === false) {
        fwrite(STDERR, "Erro ao ler: {$jsonPath}\n");
        $errors++;
        continue;
    }

    $data = json_decode($content, true);
    if (!is_array($data)) {
        fwrite(STDERR, "JSON inválido: {$jsonPath}\n");
        $errors++;
        continue;
    }

    foreach ($formats as $format => $config) {
        $targetRel = $relDir . '/' . $base . '.' . $config['ext'];
        if ($format === 'csv' && str_contains(str_replace('\\', '/', $relPath), 'odbc/odbc_')) {
            $targetRel = $csvOdbcRename($targetRel);
        }
        $targetPath = $baseDir . '/' . $format . '/' . $targetRel;

        $targetDir = dirname($targetPath);
        if (!is_dir($targetDir)) {
            continue;
        }

        $converted = match ($config['converter']) {
            'toCsv'  => toCsv($data),
            'toIni'  => toIni($data),
            'toNeon' => toNeon($data),
            'toXml'  => toXml($data),
            'toYaml' => toYaml($data),
            default  => null,
        };

        if ($converted === null) {
            continue;
        }

        $existing = @file_get_contents($targetPath);
        if ($existing !== false && $existing === $converted) {
            continue;
        }

        if (@file_put_contents($targetPath, $converted) === false) {
            fwrite(STDERR, "Erro ao escrever: {$targetPath}\n");
            $errors++;
        } else {
            $updated++;
            echo "Atualizado: {$targetRel}\n";
        }
    }
}

echo "\nTotal atualizado: {$updated}\n";
if ($errors > 0) {
    echo "Erros: {$errors}\n";
    exit(1);
}

// --- Conversores (espelham o padrão dos arquivos existentes) ---

function toCsv(array $data): string
{
    $keys = [];
    $row  = [];
    foreach ($data as $k => $v) {
        $keys[] = $k;
        if ($k === 'options' && is_array($v)) {
            $opt     = $v[0] ?? $v;
            $row[]   = is_array($opt) ? json_encode([$opt]) : $v;
        } else {
            $row[] = $v;
        }
    }

    $escape = static function ($x) {
        $s = (string) (is_bool($x) ? ($x ? 'true' : 'false') : $x);
        return '"' . str_replace('"', '""', $s) . '"';
    };

    return implode(',', $keys) . "\n" . implode(',', array_map($escape, $row)) . "\n";
}

function toIni(array $data): string
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

function toNeon(array $data): string
{
    $lines = [];
    foreach ($data as $k => $v) {
        if ($k === 'options' && is_array($v)) {
            $lines[] = 'options:';
            $opt     = $v[0] ?? $v;
            if (is_array($opt)) {
                foreach ($opt as $ok => $ov) {
                    $oval = is_bool($ov) ? ($ov ? 'true' : 'false') : $ov;
                    $lines[] = '  ' . $ok . ': ' . (is_numeric($oval) ? $oval : (str_contains((string) $oval, '::') ? '"' . $oval . '"' : $oval));
                }
            }
            continue;
        }
        $val = is_bool($v) ? ($v ? 'true' : 'false') : $v;
        $lines[] = $k . ': ' . (is_numeric($val) ? $val : (string) $val);
    }
    return implode("\n", $lines) . "\n";
}

function toXml(array $data): string
{
    $root = array_key_exists('host', $data) ? 'root' : 'config';
    $xml  = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n<{$root}>\n";

    foreach ($data as $k => $v) {
        if ($k === 'options' && is_array($v)) {
            $xml .= "\t<options>\n";
            $opt = $v[0] ?? $v;
            if (is_array($opt)) {
                foreach ($opt as $ok => $ov) {
                    $oval = is_bool($ov) ? ($ov ? 'true' : 'false') : $ov;
                    $xml .= "\t\t<option name=\"" . htmlspecialchars($ok, ENT_XML1) . "\">" . htmlspecialchars((string) $oval, ENT_XML1) . "</option>\n";
                }
            }
            $xml .= "\t</options>\n";
            continue;
        }
        $val = is_bool($v) ? ($v ? 'true' : 'false') : $v;
        $xml .= "\t<{$k}>" . htmlspecialchars((string) $val, ENT_XML1) . "</{$k}>\n";
    }
    $xml .= "</{$root}>\n";
    return $xml;
}

function toYaml(array $data): string
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
                    $key  = (str_contains($ok, '::') ? '"' . $ok . '"' : $ok);
                    if ($first) {
                        $lines[] = '  - ' . $key . ': ' . (is_numeric($oval) ? $oval : $oval);
                        $first   = false;
                    } else {
                        $lines[] = '    ' . $key . ': ' . (is_numeric($oval) ? $oval : (str_contains((string) $oval, '::') ? '"' . $oval . '"' : $oval));
                    }
                }
            }
            continue;
        }
        $val = is_bool($v) ? ($v ? 'true' : 'false') : $v;
        $lines[] = $k . ': ' . (is_numeric($val) ? $val : (string) $val);
    }
    return implode("\n", $lines) . "\n";
}
