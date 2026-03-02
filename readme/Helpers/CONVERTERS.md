# Converters

`src/Helpers/Converters/` — DSN file format conversion subsystem.

Reads a source format directory (any of the six supported formats) and converts
its records to one or more target format directories, converting each record on
the fly. Only files whose content changes are actually written (idempotent).
Files are skipped when the target subdirectory does not exist, preserving the
original directory topology.

**Supported formats (source and target):** JSON · CSV · INI · NEON · XML · YAML

---

## Table of Contents

- [Overview](#overview)
- [Directory Structure](#directory-structure)
- [Architecture](#architecture)
  - [Orchestrator — Converter](#orchestrator--converter)
  - [Result Object — ConverterResult](#result-object--converterresult)
  - [Readers](#readers)
  - [Writers](#writers)
- [Usage](#usage)
  - [JSON → all formats](#json--all-formats)
  - [CSV → JSON (reverse)](#csv--json-reverse)
  - [Any format → any format](#any-format--any-format)
  - [Inspecting results](#inspecting-results)
  - [OOP script example](#oop-script-example)
- [Class Reference](#class-reference)
  - [Converter](#converter)
  - [ConverterResult](#converterresult)
  - [BaseReader](#basereader)
  - [JsonReader](#jsonreader)
  - [CsvReader](#csvreader)
  - [IniReader](#inireader)
  - [NeonReader](#neonreader)
  - [XmlReader](#xmlreader)
  - [YamlReader](#yamlreader)
  - [BaseWriter](#basewriter)
  - [JsonWriter](#jsonwriter)
  - [CsvWriter](#csvwriter)
  - [IniWriter](#iniwriter)
  - [NeonWriter](#neonwriter)
  - [XmlWriter](#xmlwriter)
  - [YamlWriter](#yamlwriter)
- [Design Patterns](#design-patterns)
- [Extending the Subsystem](#extending-the-subsystem)
  - [Adding a new format](#adding-a-new-format)
- [Special Behaviours](#special-behaviours)

---

## Overview

The Converters subsystem provides bidirectional conversion between all six DSN
flat-file formats. Any format can be the source; any combination of formats can
be the target. This enables:

- Regenerating derived formats from the canonical JSON source of truth.
- Round-tripping between formats (e.g. XML → INI → YAML).
- Recovering the JSON source from any other format.

Key properties:

- **Bidirectional** — every format is both a valid source and a valid target.
- **Idempotent** — files that already contain the correct content are not touched.
- **Selective** — only writes to format subdirectories that already exist in the
  target path (preserves the original directory structure without creating new folders).
- **Extensible** — adding a new format requires implementing one reader class and
  one writer class, then registering two methods on the orchestrator.

---

## Directory Structure

```
src/Helpers/Converters/
├── Converter.php            # Central orchestrator (fluent API)
├── ConverterResult.php      # Per-format result object
├── Readers/
│   ├── BaseReader.php       # Abstract base; shared coerce helpers
│   ├── JsonReader.php       # JSON directory reader
│   ├── CsvReader.php        # CSV directory reader
│   ├── IniReader.php        # INI directory reader
│   ├── NeonReader.php       # NEON directory reader
│   ├── XmlReader.php        # XML directory reader
│   └── YamlReader.php       # YAML directory reader
└── Writers/
    ├── BaseWriter.php       # Abstract base; idempotent write()
    ├── JsonWriter.php       # JSON format writer
    ├── CsvWriter.php        # CSV format writer
    ├── IniWriter.php        # INI format writer
    ├── NeonWriter.php       # NEON format writer
    ├── XmlWriter.php        # XML format writer
    └── YamlWriter.php       # YAML format writer
```

---

## Architecture

### Orchestrator — Converter

`Converter` is the single entry point. It follows the same fluent factory pattern
used by `Export` (Exporters subsystem) and `Migrate` (Migrators subsystem):

1. A **private constructor** receives an abstract `BaseReader`.
2. **Static factory methods** (`fromJson()`, `fromCsv()`, …) instantiate the concrete
   reader and return the orchestrator.
3. **Fluent target methods** (`toJson()`, `toCsv()`, `toIni()`, `toNeon()`, `toXml()`,
   `toYaml()`) invoke the internal conversion engine and return `$this` for chaining.
4. **Result accessors** (`getConverterResults()`, `getConverterResult()`) expose
   `ConverterResult` objects after execution.

### Result Object — ConverterResult

`ConverterResult` records the outcome of one source → one target format pass:

| State       | Meaning                                               |
|-------------|-------------------------------------------------------|
| `updated`   | File was written (new or changed content)             |
| `unchanged` | File already had the correct content — not touched    |
| `skipped`   | Target subdirectory does not exist — bypassed         |
| `error`     | Write operation failed                                |

### Readers

All readers extend `BaseReader`. The constructor validates the source directory
and calls the abstract `load()` method, which populates `$this->records` —
a map of `relPath (string) => decoded record (array<string, mixed>)`.

Each reader parses only the specific pattern subset emitted by the corresponding
writer, without relying on external format libraries.

```
BaseReader (abstract) — coerceValue / coerceNeonValue / coerceIniValue / parseKeyValue
├── JsonReader    — json_decode on each *.json file
├── CsvReader     — str_getcsv (header + data row); JSON-decode options cell
├── IniReader     — manual line parser; stripslashes for quoted strings
├── NeonReader    — manual line parser; two-space-indented options block
├── XmlReader     — SimpleXML; <options><option name=""> children
└── YamlReader    — manual line parser; sequence mapping options block
```

### Writers

All writers extend `BaseWriter`. They implement:

- `convert(array $data): string` — serializes the record to the target format string.
- `transformRelPath(string $relPath): string` — optionally rewrites the relative output
  path (used by `CsvWriter` for the ODBC legacy rename).

The shared `write()` method handles idempotency checking and raises `RuntimeException`
on write failure.

```
BaseWriter (abstract) — idempotent write()
├── JsonWriter   — json_encode with JSON_PRETTY_PRINT
├── CsvWriter    — comma-separated; options JSON-encoded in one cell
├── IniWriter    — key=value; options as options["key"]=value
├── NeonWriter   — NEON block; options as two-space-indented nested block
├── XmlWriter    — XML with <options><option name=""> children
└── YamlWriter   — YAML block scalars; options as sequence mapping
```

---

## Usage

### JSON → all formats

```php
use GenericDatabase\Helpers\Converters\Converter;

Converter::fromJson('/path/to/resources/dsn/json')
    ->toCsv('/path/to/resources/dsn/csv')
    ->toIni('/path/to/resources/dsn/ini')
    ->toNeon('/path/to/resources/dsn/neon')
    ->toXml('/path/to/resources/dsn/xml')
    ->toYaml('/path/to/resources/dsn/yaml');
```

### CSV → JSON (reverse)

```php
Converter::fromCsv('/path/to/resources/dsn/csv')
    ->toJson('/path/to/resources/dsn/json');
```

### Any format → any format

```php
// XML → INI + NEON
Converter::fromXml('/path/to/resources/dsn/xml')
    ->toIni('/path/to/resources/dsn/ini')
    ->toNeon('/path/to/resources/dsn/neon');

// YAML → CSV + JSON
Converter::fromYaml('/path/to/resources/dsn/yaml')
    ->toCsv('/path/to/resources/dsn/csv')
    ->toJson('/path/to/resources/dsn/json');
```

### Inspecting results

```php
$converter = Converter::fromJson('/path/to/resources/dsn/json')
    ->toCsv('/path/to/resources/dsn/csv')
    ->toIni('/path/to/resources/dsn/ini');

// Inspect discovered source files
$reader = $converter->getReader();
echo count($reader->getRecords()) . " file(s) found in " . $reader->getSourceDir() . "\n";

// Inspect per-format outcome
foreach ($converter->getConverterResults() as $format => $result) {
    echo $result->getSummary() . PHP_EOL;

    foreach ($result->getUpdated() as $path) {
        echo "  Updated: {$path}\n";
    }
    foreach ($result->getErrors() as $path => $msg) {
        echo "  Error:   {$path} — {$msg}\n";
    }
}
```

### OOP script example

See [`./.devtools/.source/convert-dsn-from-json-oop.php`](./.devtools/.source/convert-dsn-from-json-oop.php).

The original standalone script [`./.devtools/.source/convert-dsn-from-json-oop.php`](./.devtools/.source/convert-dsn-from-json.php)
remains untouched and fully functional as a zero-dependency alternative.

---

## Class Reference

### Converter

**Namespace:** `GenericDatabase\Helpers\Converters`

**Source factory methods:**

| Member | Description |
|--------|-------------|
| `static fromJson(string $sourceDir): self` | Read DSN records from JSON files |
| `static fromCsv(string $sourceDir): self` | Read DSN records from CSV files |
| `static fromIni(string $sourceDir): self` | Read DSN records from INI files |
| `static fromNeon(string $sourceDir): self` | Read DSN records from NEON files |
| `static fromXml(string $sourceDir): self` | Read DSN records from XML files |
| `static fromYaml(string $sourceDir): self` | Read DSN records from YAML files |

**Target format methods (fluent):**

| Member | Description |
|--------|-------------|
| `toJson(string $targetDir): self` | Convert to JSON format |
| `toCsv(string $targetDir): self` | Convert to CSV format |
| `toIni(string $targetDir): self` | Convert to INI format |
| `toNeon(string $targetDir): self` | Convert to NEON format |
| `toXml(string $targetDir): self` | Convert to XML format |
| `toYaml(string $targetDir): self` | Convert to YAML format |

**Result accessors:**

| Member | Description |
|--------|-------------|
| `getConverterResults(): array<string, ConverterResult>` | All results keyed by target format name |
| `getConverterResult(string $format): ?ConverterResult` | Single format result (`null` if not run) |
| `getReader(): BaseReader` | The underlying source reader instance |

### ConverterResult

**Namespace:** `GenericDatabase\Helpers\Converters`

| Member | Description |
|--------|-------------|
| `getSourceFormat(): string` | Source format name (e.g. `'json'`) |
| `getTargetFormat(): string` | Target format name (e.g. `'csv'`) |
| `getTargetDir(): string` | Absolute target directory path |
| `getUpdated(): list<string>` | Relative paths of files that were written |
| `getUnchanged(): list<string>` | Relative paths of files skipped (identical content) |
| `getSkipped(): array<string, string>` | `relPath => skip reason` for bypassed files |
| `getErrors(): array<string, string>` | `relPath => error message` for failed writes |
| `getTotalUpdated(): int` | Count of files actually written |
| `isSuccess(): bool` | `true` when no write errors occurred |
| `getSummary(): string` | Human-readable one-line summary |

### BaseReader

**Namespace:** `GenericDatabase\Helpers\Converters\Readers`

| Member | Description |
|--------|-------------|
| `__construct(string $sourceDir)` | Validates directory and calls `load()` |
| `abstract load(): void` | Populate `$this->records` |
| `abstract getSourceFormat(): string` | Return the canonical format identifier |
| `getRecords(): array<string, array>` | All decoded records keyed by relative path |
| `getSourceDir(): string` | Absolute source directory path |
| `coerceValue(string): int\|float\|bool\|string` | Numeric / boolean / string inference |
| `coerceNeonValue(string): int\|float\|bool\|string` | Strips `"…"` quotes (NEON/YAML); then `coerceValue` |
| `coerceIniValue(string): int\|float\|bool\|string` | Strips `"…"` + stripslashes (INI); then `coerceValue` |
| `parseKeyValue(string): ?array` | Parses `key: value` with optional quoted key containing `::` |

### JsonReader

**Namespace:** `GenericDatabase\Helpers\Converters\Readers`

Recursively scans `*.json` files, decodes with `json_decode()`.
Files with invalid JSON or non-array root are skipped silently.

| Member | Description |
|--------|-------------|
| `getSourceFormat(): string` | Returns `'json'` |

### CsvReader

**Namespace:** `GenericDatabase\Helpers\Converters\Readers`

Parses two-row CSV files (header + data). Uses `str_getcsv()` for unquoting.
The `options` cell (JSON-encoded string) is decoded back to a nested array.
Numeric and boolean string values are coerced to native PHP types.

| Member | Description |
|--------|-------------|
| `getSourceFormat(): string` | Returns `'csv'` |

### IniReader

**Namespace:** `GenericDatabase\Helpers\Converters\Readers`

Manual line-by-line INI parser. Handles `key="value"` (addslashes-escaped) and
`options["OptionKey"]=value` array entries. Does not use `parse_ini_string` to
avoid issues with `::` in option key names.

| Member | Description |
|--------|-------------|
| `getSourceFormat(): string` | Returns `'ini'` |

### NeonReader

**Namespace:** `GenericDatabase\Helpers\Converters\Readers`

Manual parser for the NEON subset written by NeonWriter. Detects the `options:`
block by its two-space indentation. Keys in the options block are unquoted (even
for `::` identifiers — NEON does not require quoting there).

| Member | Description |
|--------|-------------|
| `getSourceFormat(): string` | Returns `'neon'` |

### XmlReader

**Namespace:** `GenericDatabase\Helpers\Converters\Readers`

Parses XML with `SimpleXML`. Supports both root element variants (`<root>` for
network DSNs and `<config>` for flat-file DSNs). Decodes `<options>/<option name="">`
children back into the nested array structure.

| Member | Description |
|--------|-------------|
| `getSourceFormat(): string` | Returns `'xml'` |

### YamlReader

**Namespace:** `GenericDatabase\Helpers\Converters\Readers`

Manual parser for the YAML subset written by YamlWriter. Detects the `options:`
block; first sequence item starts with `  - `, subsequent items with `    `.
Keys containing `::` are double-quoted in YAML; `parseKeyValue()` handles both
quoted and unquoted key styles.

| Member | Description |
|--------|-------------|
| `getSourceFormat(): string` | Returns `'yaml'` |

### BaseWriter

**Namespace:** `GenericDatabase\Helpers\Converters\Writers`

| Member | Description |
|--------|-------------|
| `abstract convert(array $data): string` | Serialize a record to the target format string |
| `abstract getExtension(): string` | File extension without dot |
| `abstract getFormat(): string` | Format identifier used as result key |
| `transformRelPath(string $relPath): string` | Optional path rewrite hook (default: identity) |
| `write(string $targetDir, string $relPath, string $content): bool` | Write file; returns `true` if written, `false` if unchanged; throws `RuntimeException` on failure |

### JsonWriter

| Member | Description |
|--------|-------------|
| `convert(array $data): string` | `json_encode` with `JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE` |
| `getExtension(): string` | `'json'` |
| `getFormat(): string` | `'json'` |

### CsvWriter

| Member | Description |
|--------|-------------|
| `convert(array $data): string` | Header row + data row; `options` serialized as JSON string in one cell |
| `getExtension(): string` | `'csv'` |
| `getFormat(): string` | `'csv'` |
| `transformRelPath(string $relPath): string` | Renames `odbc/odbc_*` paths to `odbc/ocbc_*` (legacy convention) |

### IniWriter

| Member | Description |
|--------|-------------|
| `convert(array $data): string` | `key=value` pairs; strings double-quoted with addslashes; `options` as `options["key"]=value` |
| `getExtension(): string` | `'ini'` |
| `getFormat(): string` | `'ini'` |

### NeonWriter

| Member | Description |
|--------|-------------|
| `convert(array $data): string` | `key: value` pairs; `options` as two-space-indented nested NEON block; values with `::` double-quoted |
| `getExtension(): string` | `'neon'` |
| `getFormat(): string` | `'neon'` |

### XmlWriter

| Member | Description |
|--------|-------------|
| `convert(array $data): string` | `<root>` or `<config>` wrapper; `options` as `<options>/<option name="">` elements |
| `getExtension(): string` | `'xml'` |
| `getFormat(): string` | `'xml'` |

### YamlWriter

| Member | Description |
|--------|-------------|
| `convert(array $data): string` | `key: value` pairs; `options` as YAML block sequence with single mapping item; `::` keys/values double-quoted |
| `getExtension(): string` | `'yaml'` |
| `getFormat(): string` | `'yaml'` |

---

## Design Patterns

| Pattern | Where applied |
|---------|---------------|
| **Private constructor + static factory** | `Converter::fromJson()` etc. — forces named entry points, hides construction |
| **Fluent interface** | All target methods return `$this`, enabling natural-language chaining |
| **Template method** | `BaseReader::__construct()` calls abstract `load()`; `BaseWriter::write()` calls abstract `convert()` |
| **Strategy** | Each `BaseReader` / `BaseWriter` subclass is an interchangeable, self-contained strategy |
| **Composition** | `Converter` composes a `BaseReader`; the conversion engine composes concrete `BaseWriter` instances per call |
| **Idempotency** | `BaseWriter::write()` reads the existing file and skips the write when content matches |

---

## Extending the Subsystem

### Adding a new format

To add support for a new format `xyz`:

**1. Create the reader** `src/Helpers/Converters/Readers/XyzReader.php`:

```php
class XyzReader extends BaseReader
{
    protected function load(): void
    {
        // Iterate *.xyz files recursively, decode each into an array,
        // store in $this->records[$relPath] = $data
    }

    public function getSourceFormat(): string
    {
        return 'xyz';
    }
}
```

**2. Create the writer** `src/Helpers/Converters/Writers/XyzWriter.php`:

```php
class XyzWriter extends BaseWriter
{
    public function convert(array $data): string
    {
        // Serialize $data to XYZ format string
    }

    public function getExtension(): string { return 'xyz'; }
    public function getFormat(): string    { return 'xyz'; }
}
```

**3. Register in `Converter.php`**:

```php
// Add use statements
use GenericDatabase\Helpers\Converters\Readers\XyzReader;
use GenericDatabase\Helpers\Converters\Writers\XyzWriter;

// Add source factory
public static function fromXyz(string $sourceDir): self
{
    return new self(new XyzReader($sourceDir));
}

// Add target method
public function toXyz(string $targetDir): self
{
    return $this->convertFormat(new XyzWriter(), $targetDir);
}
```

---

## Special Behaviours

### CSV ODBC filename rename

In the `csv/` directory a legacy naming convention is preserved: files under the
`odbc/` subdirectory whose source name starts with `odbc_` are written with the
prefix `ocbc_` instead (e.g. `odbc_mysql.json` → `ocbc_mysql.csv`).
This is handled transparently by `CsvWriter::transformRelPath()` and requires no
configuration.

### Selective directory conversion

If a target format subdirectory does not exist at conversion time, the corresponding
file is recorded as **skipped** rather than causing an error. This preserves the
original directory topology and avoids creating directories not originally provisioned.

### Idempotency

Every `write()` call reads the current content of the target file before writing.
If the serialized content is identical to what is already on disk, the write is
suppressed and the file is recorded as **unchanged**. This makes the converter safe
to run repeatedly without producing spurious filesystem modifications.

### Type fidelity across formats

All readers implement type coercion to restore native PHP types (integer, float,
bool) from text representations:

| Reader      | Coerce method used     | Quote handling                          |
|-------------|------------------------|-----------------------------------------|
| `JsonReader` | `json_decode` (native) | N/A — JSON preserves types              |
| `CsvReader`  | `coerceValue`          | `str_getcsv` handles CSV unquoting      |
| `IniReader`  | `coerceIniValue`       | Strips `"…"` + `stripslashes`           |
| `NeonReader` | `coerceNeonValue`      | Strips `"…"` (no addslashes in NEON)    |
| `XmlReader`  | `coerceValue`          | `htmlspecialchars_decode` via SimpleXML |
| `YamlReader` | `coerceNeonValue`      | Same quoting convention as NEON         |
