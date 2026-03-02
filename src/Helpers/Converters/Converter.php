<?php

namespace GenericDatabase\Helpers\Converters;

use GenericDatabase\Helpers\Converters\Readers\BaseReader;
use GenericDatabase\Helpers\Converters\Readers\JsonReader;
use GenericDatabase\Helpers\Converters\Readers\CsvReader;
use GenericDatabase\Helpers\Converters\Readers\IniReader;
use GenericDatabase\Helpers\Converters\Readers\NeonReader;
use GenericDatabase\Helpers\Converters\Readers\XmlReader;
use GenericDatabase\Helpers\Converters\Readers\YamlReader;
use GenericDatabase\Helpers\Converters\Writers\BaseWriter;
use GenericDatabase\Helpers\Converters\Writers\JsonWriter;
use GenericDatabase\Helpers\Converters\Writers\CsvWriter;
use GenericDatabase\Helpers\Converters\Writers\IniWriter;
use GenericDatabase\Helpers\Converters\Writers\NeonWriter;
use GenericDatabase\Helpers\Converters\Writers\XmlWriter;
use GenericDatabase\Helpers\Converters\Writers\YamlWriter;
use RuntimeException;

/**
 * Converter — Central orchestrator for DSN file format conversions.
 *
 * Reads a source format directory and converts its records to one or more target
 * format directories. Any of the six supported formats can be the source, and any
 * combination of the six can be the target.
 *
 * Supported formats: json, csv, ini, neon, xml, yaml.
 *
 * Only files whose content has changed are written (idempotent). Files are skipped
 * when the target subdirectory does not exist, preserving the directory topology.
 *
 * Fluent interface: chain source factory → target method(s) → inspect results.
 *
 * Usage (JSON → all formats):
 *
 * ```php
 * $converter = Converter::fromJson('/path/to/resources/dsn/json')
 *     ->toCsv('/path/to/resources/dsn/csv')
 *     ->toIni('/path/to/resources/dsn/ini')
 *     ->toNeon('/path/to/resources/dsn/neon')
 *     ->toXml('/path/to/resources/dsn/xml')
 *     ->toYaml('/path/to/resources/dsn/yaml');
 * ```
 *
 * Usage (CSV → JSON):
 *
 * ```php
 * $converter = Converter::fromCsv('/path/to/resources/dsn/csv')
 *     ->toJson('/path/to/resources/dsn/json');
 * ```
 *
 * Usage (XML → INI + NEON):
 *
 * ```php
 * $converter = Converter::fromXml('/path/to/resources/dsn/xml')
 *     ->toIni('/path/to/resources/dsn/ini')
 *     ->toNeon('/path/to/resources/dsn/neon');
 * ```
 *
 * Usage (inspecting results):
 *
 * ```php
 * foreach ($converter->getConverterResults() as $format => $result) {
 *     echo $result->getSummary() . PHP_EOL;
 * }
 * ```
 */
class Converter
{
    private BaseReader $reader;

    /** @var array<string, ConverterResult> format => result */
    private array $results = [];

    private function __construct(BaseReader $reader)
    {
        $this->reader = $reader;
    }

    // -----------------------------------------------------------------------
    // Source factory methods
    // -----------------------------------------------------------------------

    /**
     * Read DSN records from a directory of JSON files.
     *
     * @throws RuntimeException When the directory does not exist
     */
    public static function fromJson(string $sourceDir): self
    {
        return new self(new JsonReader($sourceDir));
    }

    /**
     * Read DSN records from a directory of CSV files.
     *
     * @throws RuntimeException When the directory does not exist
     */
    public static function fromCsv(string $sourceDir): self
    {
        return new self(new CsvReader($sourceDir));
    }

    /**
     * Read DSN records from a directory of INI files.
     *
     * @throws RuntimeException When the directory does not exist
     */
    public static function fromIni(string $sourceDir): self
    {
        return new self(new IniReader($sourceDir));
    }

    /**
     * Read DSN records from a directory of NEON files.
     *
     * @throws RuntimeException When the directory does not exist
     */
    public static function fromNeon(string $sourceDir): self
    {
        return new self(new NeonReader($sourceDir));
    }

    /**
     * Read DSN records from a directory of XML files.
     *
     * @throws RuntimeException When the directory does not exist
     */
    public static function fromXml(string $sourceDir): self
    {
        return new self(new XmlReader($sourceDir));
    }

    /**
     * Read DSN records from a directory of YAML files.
     *
     * @throws RuntimeException When the directory does not exist
     */
    public static function fromYaml(string $sourceDir): self
    {
        return new self(new YamlReader($sourceDir));
    }

    // -----------------------------------------------------------------------
    // Internal conversion engine
    // -----------------------------------------------------------------------

    /**
     * Core conversion loop.
     *
     * For every source record:
     *   1. Derive the target relative path (swap extension, apply writer transforms).
     *   2. Skip if the target subdirectory does not exist (preserves directory topology).
     *   3. Convert the record and write — or no-op if content is unchanged.
     *
     * @param BaseWriter $writer    The format-specific converter/writer
     * @param string     $targetDir Absolute base directory for the target format
     */
    private function convertFormat(BaseWriter $writer, string $targetDir): self
    {
        $result = new ConverterResult($this->reader->getSourceFormat(), $writer->getFormat(), $targetDir);

        foreach ($this->reader->getRecords() as $sourceRelPath => $data) {
            $relDir  = dirname($sourceRelPath);
            $base    = pathinfo($sourceRelPath, PATHINFO_FILENAME);
            $relPath = ($relDir === '.' ? '' : $relDir . '/') . $base . '.' . $writer->getExtension();

            // Apply writer-specific path transformations (e.g. CSV ODBC legacy rename)
            $relPath = $writer->transformRelPath($relPath);

            // Only write if the target subdirectory already exists
            $subDir = rtrim(str_replace('\\', '/', $targetDir), '/') . '/' . dirname($relPath);
            if (!is_dir($subDir)) {
                $result->addSkipped($relPath, 'Target subdirectory does not exist');
                continue;
            }

            $content = $writer->convert($data);

            try {
                $written = $writer->write($targetDir, $relPath, $content);
                if ($written) {
                    $result->addUpdated($relPath);
                } else {
                    $result->addUnchanged($relPath);
                }
            } catch (RuntimeException $e) {
                $result->addError($relPath, $e->getMessage());
            }
        }

        $this->results[$writer->getFormat()] = $result;

        return $this;
    }

    // -----------------------------------------------------------------------
    // Target format methods (fluent)
    // -----------------------------------------------------------------------

    /**
     * Convert to JSON format.
     *
     * @param string $targetDir Absolute path to the JSON target directory
     */
    public function toJson(string $targetDir): self
    {
        return $this->convertFormat(new JsonWriter(), $targetDir);
    }

    /**
     * Convert to CSV format.
     *
     * @param string $targetDir Absolute path to the CSV target directory
     */
    public function toCsv(string $targetDir): self
    {
        return $this->convertFormat(new CsvWriter(), $targetDir);
    }

    /**
     * Convert to INI format.
     *
     * @param string $targetDir Absolute path to the INI target directory
     */
    public function toIni(string $targetDir): self
    {
        return $this->convertFormat(new IniWriter(), $targetDir);
    }

    /**
     * Convert to NEON format.
     *
     * @param string $targetDir Absolute path to the NEON target directory
     */
    public function toNeon(string $targetDir): self
    {
        return $this->convertFormat(new NeonWriter(), $targetDir);
    }

    /**
     * Convert to XML format.
     *
     * @param string $targetDir Absolute path to the XML target directory
     */
    public function toXml(string $targetDir): self
    {
        return $this->convertFormat(new XmlWriter(), $targetDir);
    }

    /**
     * Convert to YAML format.
     *
     * @param string $targetDir Absolute path to the YAML target directory
     */
    public function toYaml(string $targetDir): self
    {
        return $this->convertFormat(new YamlWriter(), $targetDir);
    }

    // -----------------------------------------------------------------------
    // Result accessors
    // -----------------------------------------------------------------------

    /**
     * Return all conversion results keyed by target format name.
     *
     * @return array<string, ConverterResult>
     */
    public function getConverterResults(): array
    {
        return $this->results;
    }

    /**
     * Return the conversion result for a specific target format.
     *
     * @param string $format Format key ('json', 'csv', 'ini', 'neon', 'xml', 'yaml')
     */
    public function getConverterResult(string $format): ?ConverterResult
    {
        return $this->results[$format] ?? null;
    }

    /**
     * Return the source reader instance (useful for inspecting discovered records).
     */
    public function getReader(): BaseReader
    {
        return $this->reader;
    }
}
