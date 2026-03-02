# Helpers / Exporters

Export data from any supported database engine to flat-file formats (CSV, XML, JSON, YAML, INI, NEON) with automatic `Schema.ini` generation.

---

## Table of Contents

- [Overview](#overview)
- [Architecture](#architecture)
- [Quick Start](#quick-start)
- [Source Engines](#source-engines)
  - [SQLite](#sqlite)
  - [MySQL / MariaDB (MySQLi)](#mysql--mariadb-mysqli)
  - [PostgreSQL](#postgresql)
  - [Oracle (OCI8)](#oracle-oci8)
  - [Firebird](#firebird)
  - [ODBC](#odbc)
  - [PDO (generic)](#pdo-generic)
- [Output Formats](#output-formats)
  - [CSV](#csv)
  - [XML](#xml)
  - [JSON](#json)
  - [YAML](#yaml)
  - [INI](#ini)
  - [NEON](#neon)
- [Schema.ini](#schemaini)
- [Fluent API Reference](#fluent-api-reference)
- [Working with Results](#working-with-results)
- [Type Normalisation](#type-normalisation)
- [Foreign Key Detection](#foreign-key-detection)
- [File Structure](#file-structure)

---

## Overview

The Exporters subsystem reads the full structure (schema + data) of a database and writes each table to one or more flat-file formats.
Every export also produces a **`Schema.ini`** file that describes column types, primary keys, and foreign key relationships — making the exported files self-describing and consumable by flat-file readers.

```
Database → Export → [CSV | XML | JSON | YAML | INI | NEON] + Schema.ini
```

---

## Architecture

```
Export (orchestrator)
│
├── Static factories: fromSQLite(), fromMySQLi(), fromPgSQL(), …
│       │
│       └── creates → Readers\BaseExporter subclass (source reader)
│               ├── Readers\SQLiteExporter     (ext-sqlite3)
│               ├── Readers\MySQLiExporter     (ext-mysqli)
│               ├── Readers\PgSQLExporter      (ext-pgsql)
│               ├── Readers\OCIExporter        (ext-oci8)
│               ├── Readers\FirebirdExporter   (ext-interbase)
│               ├── Readers\ODBCExporter       (ext-odbc)
│               └── Readers\PDOExporter        (ext-pdo + driver)
│
└── Export methods: toCSV(), toXML(), toJSON(), toYAML(), toINI(), toNEON()
        │
        └── creates → Writers\* format exporter (writer)
                ├── Writers\CSVExporter
                ├── Writers\XMLExporter
                ├── Writers\JSONExporter
                ├── Writers\YAMLExporter
                ├── Writers\INIExporter
                └── Writers\NEONExporter
                        │
                        └── Writers\SchemaGenerator (Schema.ini)
```

### Design Patterns Used

| Pattern | Where |
|---------|-------|
| Factory | `Export::fromXxx()` static methods |
| Template Method | `BaseExporter` defines interface; subclasses implement engine queries |
| Strategy | Swappable source exporters via `BaseExporter` |
| Composition | Format exporters receive a `BaseExporter` instance |
| Fluent Interface | `Export::toCSV()->toXML()->…` chain |

---

## Quick Start

```php
require_once __DIR__ . '/../vendor/autoload.php';

use GenericDatabase\Helpers\Exporters\Export;

$export = Export::fromSQLite('/path/to/DB.SQLITE', '/path/to/base')
    ->toCSV('/path/to/csv')
    ->toXML('/path/to/xml')
    ->toJSON('/path/to/json')
    ->toYAML('/path/to/yaml')
    ->toINI('/path/to/ini')
    ->toNEON('/path/to/neon');

foreach ($export->getExportedPaths() as $format => $files) {
    echo "$format:\n";
    foreach ($files as $file) {
        echo "  " . basename($file) . "\n";
    }
}
```

Run via command line:

```bash
php scripts/export-sqlite-to-formats.php
```

---

## Source Engines

### SQLite

Uses the `sqlite3` PHP extension.

```php
Export::fromSQLite(
    databasePath: '/absolute/path/to/DB.SQLITE',
    outputPath:   '/tmp/export-base'
);
```

**Requirements:** `ext-sqlite3`

Schema discovery uses `PRAGMA table_info()` and `PRAGMA foreign_key_list()`.

---

### MySQL / MariaDB (MySQLi)

Uses the `mysqli` PHP extension.

```php
Export::fromMySQLi(
    host:       'localhost',
    user:       'root',
    password:   'secret',
    database:   'my_database',
    outputPath: '/tmp/export',
    port:       3306,           // optional, default 3306
    charset:    'utf8mb4'       // optional, default 'utf8'
);
```

**Requirements:** `ext-mysqli`

Schema discovery uses `SHOW TABLES`, `SHOW COLUMNS FROM`, and `INFORMATION_SCHEMA.KEY_COLUMN_USAGE`.

---

### PostgreSQL

Uses the `pgsql` PHP extension.

```php
Export::fromPgSQL(
    host:       'localhost',
    user:       'postgres',
    password:   'secret',
    database:   'my_database',
    outputPath: '/tmp/export',
    port:       5432            // optional, default 5432
);
```

**Requirements:** `ext-pgsql`

Schema discovery uses `pg_tables`, `information_schema.columns`, `pg_index`, and `information_schema.table_constraints`.

---

### Oracle (OCI8)

Uses the `oci8` PHP extension.

```php
Export::fromOCI(
    host:       'localhost',
    user:       'hr',
    password:   'secret',
    database:   'FREE',         // service name or SID
    outputPath: '/tmp/export',
    port:       1521,           // optional, default 1521
    charset:    'AL32UTF8'      // optional, default 'AL32UTF8'
);
```

**Requirements:** `ext-oci8`, Oracle Instant Client

Schema discovery uses `user_tables`, `user_tab_columns`, `user_constraints`, and `user_cons_columns`.
All identifiers are normalised to lowercase.

---

### Firebird

Uses the `interbase` PHP extension (ibase functions).

```php
Export::fromFirebird(
    host:       'localhost',
    user:       'SYSDBA',
    password:   'masterkey',
    database:   '/path/to/DB.FDB',
    outputPath: '/tmp/export',
    port:       3050,           // optional, default 3050
    charset:    'UTF8'          // optional, default 'UTF8'
);
```

**Requirements:** `ext-interbase`

Schema discovery uses `RDB$RELATIONS`, `RDB$RELATION_FIELDS`, `RDB$FIELDS`, `RDB$INDICES`, and `RDB$RELATION_CONSTRAINTS`.
Firebird field-type numeric codes are mapped to SQL type names automatically.

---

### ODBC

Uses the `odbc` PHP extension. Supports any ODBC driver (Access, Excel, dBase, SQL Server via ODBC, etc.).

```php
Export::fromODBC(
    driver:     'Microsoft Access Driver (*.mdb)',
    dsn:        'Driver={Microsoft Access Driver (*.mdb)};DBQ=C:\data\mydb.mdb',
    user:       '',
    password:   '',
    outputPath: '/tmp/export'
);
```

**Requirements:** `ext-odbc`, appropriate ODBC driver installed on the OS

Schema discovery uses `odbc_tables()`, `odbc_columns()`, `odbc_primarykeys()`, and `odbc_foreignkeys()`.

---

### PDO (generic)

Uses PHP PDO. Supports all PDO drivers: `mysql`, `pgsql`, `sqlite`, `oci`, `sqlsrv`, `firebird`, etc.
Driver-specific catalogue queries are selected automatically from the DSN driver name.

```php
Export::fromPDO(
    driver:     'mysql',
    dsn:        'mysql:host=localhost;port=3306;dbname=my_database;charset=utf8mb4',
    user:       'root',
    password:   'secret',
    outputPath: '/tmp/export',
    options:    [PDO::ATTR_TIMEOUT => 10]  // optional PDO options
);
```

**Requirements:** `ext-pdo`, appropriate PDO driver extension

---

## Output Formats

All format methods accept an `$outputPath` argument and return `self` for chaining.
Each call creates one file per table in the target directory.

### CSV

```php
->toCSV('/path/to/csv', delimiter: ';')  // delimiter optional, default ';'
```

- One `.csv` file per table
- First row is the header row with column names
- Values containing the delimiter are quoted
- Generates `Schema.ini` with column types

### XML

```php
->toXML('/path/to/xml')
```

- One `.xml` file per table
- Root element: `<data>`, repeated element: `<item>`
- Column names are used as child element names
- Names are sanitised for XML validity (leading digits get `_` prefix, special chars replaced with `_`)
- Generates `Schema.ini`

### JSON

```php
->toJSON('/path/to/json')
```

- One `.json` file per table
- Top-level array of objects
- Encoded with `JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES`
- Generates `Schema.ini`

### YAML

```php
->toYAML('/path/to/yaml')
```

- One `.yaml` file per table
- Requires the `yaml` PHP extension (`ext-yaml`)
- Encoded with `YAML_UTF8_ENCODING`
- Generates `Schema.ini`

**Requirements:** `ext-yaml`

### INI

```php
->toINI('/path/to/ini')
```

- One `.ini` file per table
- Each row becomes a numbered `[section_N]` with `key = value` pairs
- Values containing special characters (`;`, `"`, newlines) are automatically quoted
- Generates `Schema.ini`

### NEON

```php
->toNEON('/path/to/neon')
```

- One `.neon` file per table
- NEON is a human-friendly config format similar to YAML, used by the Nette Framework
- Requires the `nette/neon` Composer package
- Generates `Schema.ini`

**Requirements:** `nette/neon` via Composer

---

## Schema.ini

Every export call automatically generates a `Schema.ini` in the output directory.
This file provides metadata about each exported table and is understood by flat-file readers such as Microsoft OLEDB Text Driver and the project's own flat-file connection engines.

### Schema.ini Structure

```ini
; Auto-generated by SchemaGenerator
; Source: sqlite | Generated: 2026-01-01 12:00:00

[users.csv]
Format=Delimited(;)
TableName=users
DateTimeFormat=YYYY-MM-DD HH:MM:SS
PrimaryKey=id
Col1="id" Integer
Col2="name" Text
Col3="email" Text
Col4="created_at" DateTime
ForeignKeys=role_id->roles.csv(id)

[orders.csv]
; ...
```

### Normalised Types in Schema.ini

| Source DB Type | Schema.ini Type |
|---------------|-----------------|
| INT, INTEGER, BIGINT, SMALLINT, SERIAL | `Integer` |
| DATE, TIME, DATETIME, TIMESTAMP | `DateTime` |
| Everything else | `Text` |

---

## Fluent API Reference

### `Export::fromSQLite(string $databasePath, string $outputPath): self`

Creates an exporter reading from a SQLite file.

### `Export::fromMySQLi(…): self`

Creates an exporter reading from MySQL/MariaDB via MySQLi.

### `Export::fromPgSQL(…): self`

Creates an exporter reading from PostgreSQL via pgsql extension.

### `Export::fromOCI(…): self`

Creates an exporter reading from Oracle via OCI8.

### `Export::fromFirebird(…): self`

Creates an exporter reading from Firebird via interbase extension.

### `Export::fromODBC(…): self`

Creates an exporter reading from any ODBC data source.

### `Export::fromPDO(…): self`

Creates an exporter reading via PDO (generic, multi-driver).

### `->toCSV(string $outputPath, string $delimiter = ';'): self`

Exports all tables to CSV and generates Schema.ini.

### `->toXML(string $outputPath): self`

Exports all tables to XML and generates Schema.ini.

### `->toJSON(string $outputPath): self`

Exports all tables to JSON and generates Schema.ini.

### `->toYAML(string $outputPath): self`

Exports all tables to YAML and generates Schema.ini.

### `->toINI(string $outputPath): self`

Exports all tables to INI and generates Schema.ini.

### `->toNEON(string $outputPath): self`

Exports all tables to NEON and generates Schema.ini.

### `->getExportedPaths(): array`

Returns `['csv' => ['/path/to/table.csv', …], 'json' => […], …]`.

### `->getExportedFiles(string $format): array`

Returns the list of exported files for a single format key.

---

## Working with Results

```php
$export = Export::fromSQLite($db, $base)->toCSV($csvDir)->toJSON($jsonDir);

// All formats
foreach ($export->getExportedPaths() as $format => $files) {
    echo strtoupper($format) . ":\n";
    foreach ($files as $path) {
        echo "  " . basename($path) . " (" . filesize($path) . " bytes)\n";
    }
}

// Specific format
$csvFiles = $export->getExportedFiles('csv');
```

---

## Type Normalisation

`Readers\BaseExporter::normalizeType()` converts raw database types to the three Schema.ini types:

| Canonical | Raw Source Examples |
|-----------|---------------------|
| `Integer` | `INT`, `INTEGER`, `BIGINT`, `SMALLINT`, `TINYINT`, `MEDIUMINT`, `SERIAL`, `BIGSERIAL` |
| `DateTime` | `DATE`, `TIME`, `DATETIME`, `TIMESTAMP`, `DATETIME2`, `SMALLDATETIME` |
| `Text` | Everything else: `VARCHAR`, `TEXT`, `FLOAT`, `DECIMAL`, `BOOLEAN`, `BLOB`, `CLOB`, … |

---

## Foreign Key Detection

Foreign key relationships are detected through two strategies, applied in order:

### 1. Explicit catalogue queries (preferred)

Each engine exporter queries the database catalogue for declared `FOREIGN KEY` constraints:

| Engine | Query source |
|--------|-------------|
| SQLite | `PRAGMA foreign_key_list()` |
| MySQL | `INFORMATION_SCHEMA.KEY_COLUMN_USAGE` |
| PostgreSQL | `information_schema.table_constraints` |
| Oracle | `user_cons_columns` + `user_constraints` |
| Firebird | `RDB$RELATION_CONSTRAINTS` with `REFERENCES` type |
| ODBC | `odbc_foreignkeys()` |
| PDO | Driver-specific catalogue queries |

### 2. Naming convention heuristics (fallback)

When no explicit constraints are found, `Readers\BaseExporter::detectForeignKeysByConvention()` applies:

- Columns ending in `_id` are assumed to reference another table
- The referenced table is searched by exact match first, then suffix/prefix variations, then plural/singular forms
- Example: `role_id` → references table `roles` or `role`

The detected foreign keys appear in `Schema.ini` as:

```ini
ForeignKeys=role_id->roles.csv(id)
```

---

## File Structure

```
src/Helpers/Exporters/
├── Export.php               # Orchestrator (fluent factory + export methods)
│
├── Readers/                 # Source database readers
│   ├── BaseExporter.php     # Abstract source reader (normalizeType, getForeignKeys, …)
│   ├── SQLiteExporter.php   # SQLite source (ext-sqlite3)
│   ├── MySQLiExporter.php   # MySQL/MariaDB source (ext-mysqli)
│   ├── PgSQLExporter.php    # PostgreSQL source (ext-pgsql)
│   ├── OCIExporter.php      # Oracle source (ext-oci8)
│   ├── FirebirdExporter.php # Firebird source (ext-interbase)
│   ├── ODBCExporter.php     # ODBC source (ext-odbc)
│   └── PDOExporter.php      # Generic PDO source
│
└── Writers/                 # Format writers
    ├── SchemaGenerator.php  # Schema.ini generator
    ├── CSVExporter.php      # CSV format writer
    ├── XMLExporter.php      # XML format writer
    ├── JSONExporter.php     # JSON format writer
    ├── YAMLExporter.php     # YAML format writer (ext-yaml)
    ├── INIExporter.php      # INI format writer
    └── NEONExporter.php     # NEON format writer (nette/neon)

scripts/
└── export-sqlite-to-formats.php   # Example script
```
