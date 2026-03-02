# Helpers / Migrators

Migrate data from any supported source database engine to any supported target database engine with cross-engine type conversion, idempotent execution, and a fluent API.
Three connection strategies are available for target engines: **native PDO**, **native ODBC** (`ext-odbc`), and **ODBC via PDO** (`pdo_odbc`).

---

## Table of Contents

- [Overview](#overview)
- [Architecture](#architecture)
- [Quick Start](#quick-start)
- [Source Engines (Readers)](#source-engines-readers)
  - [SQLite](#sqlite-source)
  - [MySQL / MariaDB (MySQLi)](#mysql--mariadb-mysqli-source)
  - [PostgreSQL](#postgresql-source)
  - [Oracle (OCI8)](#oracle-oci8-source)
  - [Firebird](#firebird-source)
  - [SQL Server](#sql-server-source)
  - [ODBC](#odbc-source)
  - [PDO (generic)](#pdo-generic-source)
- [Target Engines (Writers)](#target-engines-writers)
  - [MySQL](#mysql-target)
  - [PostgreSQL](#postgresql-target)
  - [Oracle](#oracle-target)
  - [SQL Server](#sql-server-target)
  - [Firebird](#firebird-target)
  - [SQLite](#sqlite-target)
  - [PDO (generic)](#pdo-generic-target)
  - [ODBC (generic)](#odbc-generic-target)
- [Connection Strategies: PDO vs ODBC vs ODBCPDO](#connection-strategies-pdo-vs-odbc-vs-odbcpdo)
- [Fluent API Reference](#fluent-api-reference)
- [MigrationResult](#migrationresult)
- [Type Mapping Reference](#type-mapping-reference)
- [Idempotency](#idempotency)
- [Oracle Special Handling](#oracle-special-handling)
- [Firebird Special Handling](#firebird-special-handling)
- [Extending the System](#extending-the-system)
- [Comparison with Standalone Script](#comparison-with-standalone-script)
- [File Structure](#file-structure)

---

## Overview

The Migrators subsystem copies the full structure (DDL) and data (DML) of a source database into one or more target databases, performing cross-engine type conversion automatically.

```
Source DB → Reader → [type mapping] → WriterFactory → Writer (PDO | ODBC | ODBCPDO) → Target DB
```

Key capabilities:

- **Multi-source**: Read from SQLite, MySQL, PostgreSQL, Oracle, Firebird, SQL Server, ODBC, or any PDO source
- **Multi-target**: Write to MySQL, PostgreSQL, Oracle, SQL Server, Firebird, SQLite, or any PDO/ODBC target in a single fluent chain
- **Three connection strategies**: Each target method accepts a `$via` parameter:
  - `WriterFactory::PDO` (default) — native PDO drivers (`pdo_mysql`, `pdo_pgsql`, etc.)
  - `WriterFactory::ODBC` — native ODBC via `ext-odbc` (`odbc_connect` / `odbc_exec` / `odbc_prepare` / `odbc_execute`)
  - `WriterFactory::ODBCPDO` — ODBC bridged through PDO (`pdo_odbc`)
- **Type-safe conversion**: Two-stage type mapping (raw → canonical → target) preserves sizes (e.g. `VARCHAR(255)`)
- **Idempotent**: Tables with existing data are skipped automatically
- **Structured results**: `MigrationResult` tracks migrated, skipped, and failed tables per target

---

## Architecture

```
Migrate (orchestrator)
│
├── Static factories: fromSQLite(), fromMySQLi(), fromPgSQL(), fromSQLServer(), …
│       │
│       └── creates → Readers\BaseMigrator subclass (source reader)
│               ├── Readers\SQLiteReader       (ext-sqlite3)
│               ├── Readers\MySQLiReader       (ext-mysqli)
│               ├── Readers\PgSQLReader        (ext-pgsql)
│               ├── Readers\OCIReader          (ext-oci8)
│               ├── Readers\FirebirdReader     (ext-interbase)
│               ├── Readers\SQLSrvReader       (pdo_sqlsrv or pdo_odbc)
│               ├── Readers\ODBCReader         (ext-odbc)
│               └── Readers\PDOReader          (ext-pdo + driver)
│
└── Migration methods: toMySQL(), toPgSQL(), toOCI(), toSQLServer(), toFirebird(), toSQLite(), toPDO(), toODBC()
        │
        └── WriterFactory::create(engine, via) → BasePDOWriter | BaseODBCWriter
                │
                ├── [via = PDO — default]   Writers\PDO\*
                │       ├── Writers\PDO\MySQLPDOWriter          (pdo_mysql)
                │       ├── Writers\PDO\PgSQLPDOWriter          (pdo_pgsql)
                │       ├── Writers\PDO\OCIPDOWriter            (pdo_oci / oci8)
                │       ├── Writers\PDO\SQLSrvPDOWriter         (pdo_sqlsrv)
                │       ├── Writers\PDO\FirebirdPDOWriter       (pdo_firebird)
                │       ├── Writers\PDO\SQLitePDOWriter         (pdo_sqlite)
                │       └── Writers\PDO\PDOWriter               (generic PDO — custom DSN)
                │
                ├── [via = ODBC]            Writers\ODBC\*   (native ext-odbc, no PDO)
                │       ├── Writers\ODBC\MySQLODBCWriter     (odbc_connect, MySQL driver)
                │       ├── Writers\ODBC\PgSQLODBCWriter     (odbc_connect, PostgreSQL driver)
                │       ├── Writers\ODBC\OCIODBCWriter       (odbc_connect, Oracle driver)
                │       ├── Writers\ODBC\SQLSrvODBCWriter    (odbc_connect, SQL Server driver)
                │       ├── Writers\ODBC\FirebirdODBCWriter  (odbc_connect, Firebird driver)
                │       ├── Writers\ODBC\SQLiteODBCWriter    (odbc_connect, SQLite3 driver)
                │       └── Writers\ODBC\ODBCWriter          (generic — raw DSN or params)
                │
                └── [via = ODBCPDO]         Writers\ODBCPDO\*  (PDO over ODBC)
                        ├── Writers\ODBCPDO\MySQLODBCPDOWriter
                        ├── Writers\ODBCPDO\PgSQLODBCPDOWriter
                        ├── Writers\ODBCPDO\OCIODBCPDOWriter
                        ├── Writers\ODBCPDO\SQLSrvODBCPDOWriter
                        ├── Writers\ODBCPDO\FirebirdODBCPDOWriter
                        ├── Writers\ODBCPDO\SQLiteODBCPDOWriter
                        └── Writers\ODBCPDO\ODBCPDOWriter
                                │
                                └── returns → MigrationResult
```

### Two-Stage Type Mapping

```
Source raw type (e.g. "VARCHAR(255)")
        ↓
Readers\BaseMigrator::normalizeToCanonical()
        ↓
Canonical type (e.g. "varchar")
        ↓
Writers\BasePDOWriter::convertType(canonical, rawType)   [PDO / ODBCPDO]
  — or —
Writers\BaseODBCWriter::convertType(canonical, rawType)  [native ODBC]
        ↓
Target SQL type (e.g. MySQL: "VARCHAR(255)", Oracle: "VARCHAR2(255)")
```

Size information in the raw type (e.g. the `255` in `VARCHAR(255)`) is preserved through `extractSize()`.

### Design Patterns Used

| Pattern | Where |
|---------|-------|
| Factory | `Migrate::fromXxx()` static methods |
| Strategy | `WriterFactory::create(engine, via)` selects PDO, ODBC, or ODBCPDO writer |
| Template Method | `BasePDOWriter::migrate()` / `BaseODBCWriter::migrate()` drives the loop; subclasses override DDL/DML steps |
| Inheritance | ODBCPDO writers extend their PDO counterparts, only overriding `createConnection()`; native ODBC writers extend `BaseODBCWriter` independently |
| Fluent Interface | `Migrate::toMySQL()->toPgSQL()->…` chain |

---

## Quick Start

### SQLite → Multiple Targets (PDO — default)

```php
require_once __DIR__ . '/../vendor/autoload.php';

use GenericDatabase\Helpers\Migrators\Migrate;

$migrate = Migrate::fromSQLite('/path/to/DB.SQLITE')
    ->toMySQL('localhost', 'root', 'secret', 'demodev', 3306)
    ->toPgSQL('localhost', 'postgres', 'secret', 'demodev', 5432)
    ->toOCI('localhost', 'hr', 'secret', 'FREE', 1521)
    ->toSQLServer('localhost', 'sa', 'secret', 'demodev', 1433)
    ->toFirebird('localhost', 'SYSDBA', 'masterkey', '/path/to/TARGET.FDB')
    ->toSQLite('/path/to/target.sqlite');

foreach ($migrate->getMigrationResults() as $engine => $result) {
    echo $result->getSummary() . "\n";
}
```

### SQLite → MySQL via native ODBC (`ext-odbc`)

```php
use GenericDatabase\Helpers\Migrators\Migrate;
use GenericDatabase\Helpers\Migrators\Writers\WriterFactory;

$migrate = Migrate::fromSQLite('/path/to/DB.SQLITE')
    ->toMySQL(
        host:        'localhost',
        user:        'root',
        password:    'secret',
        database:    'demodev',
        port:        3306,
        via:         WriterFactory::ODBC,
        extraParams: ['odbc_driver' => 'MySQL ODBC 8.0 Unicode Driver']
    );
```

### SQLite → MySQL via ODBC bridged through PDO (`pdo_odbc`)

```php
use GenericDatabase\Helpers\Migrators\Migrate;
use GenericDatabase\Helpers\Migrators\Writers\WriterFactory;

$migrate = Migrate::fromSQLite('/path/to/DB.SQLITE')
    ->toMySQL(
        host:        'localhost',
        user:        'root',
        password:    'secret',
        database:    'demodev',
        port:        3306,
        via:         WriterFactory::ODBCPDO,
        extraParams: ['odbc_driver' => 'MySQL ODBC 8.0 Unicode Driver']
    );
```

### MySQL → PostgreSQL

```php
$migrate = Migrate::fromMySQLi('localhost', 'root', 'secret', 'sourcedb')
    ->toPgSQL('localhost', 'postgres', 'secret', 'targetdb');
```

### SQL Server → SQLite

```php
$migrate = Migrate::fromSQLServer('localhost', 'sa', 'secret', 'sourcedb', 1433)
    ->toSQLite('/path/to/target.sqlite');
```

Run the included example script:

```bash
php scripts/migrate-sqlite-to-databases-oop.php
```

---

## Source Engines (Readers)

### SQLite (source)

Uses the `sqlite3` PHP extension. Opens the database in read-only mode.

```php
Migrate::fromSQLite('/absolute/path/to/DB.SQLITE');
```

**Requirements:** `ext-sqlite3`

### MySQL / MariaDB (MySQLi) (source)

Uses the `mysqli` PHP extension.

```php
Migrate::fromMySQLi(
    host:     'localhost',
    user:     'root',
    password: 'secret',
    database: 'sourcedb',
    port:     3306,         // optional, default 3306
    charset:  'utf8mb4'     // optional, default 'utf8mb4'
);
```

**Requirements:** `ext-mysqli`

### PostgreSQL (source)

Uses the `pgsql` PHP extension.

```php
Migrate::fromPgSQL(
    host:     'localhost',
    user:     'postgres',
    password: 'secret',
    database: 'sourcedb',
    port:     5432          // optional, default 5432
);
```

**Requirements:** `ext-pgsql`

### Oracle (OCI8) (source)

Uses the `oci8` PHP extension.

```php
Migrate::fromOCI(
    host:     'localhost',
    user:     'hr',
    password: 'secret',
    database: 'FREE',       // service name or SID
    port:     1521,         // optional, default 1521
    charset:  'AL32UTF8'    // optional, default 'AL32UTF8'
);
```

**Requirements:** `ext-oci8`, Oracle Instant Client

### Firebird (source)

Uses the `interbase` PHP extension (ibase functions).

```php
Migrate::fromFirebird(
    host:     'localhost',
    user:     'SYSDBA',
    password: 'masterkey',
    database: '/path/to/DB.FDB',
    port:     3050,         // optional, default 3050
    charset:  'UTF8'        // optional, default 'UTF8'
);
```

**Requirements:** `ext-interbase`

### SQL Server (source)

Uses `pdo_sqlsrv` with automatic fallback to `pdo_odbc`.

```php
Migrate::fromSQLServer(
    host:     'localhost',
    user:     'sa',
    password: 'secret',
    database: 'sourcedb',
    port:     1433          // optional, default 1433
);
```

**Requirements:** `ext-pdo_sqlsrv` or `ext-pdo` + ODBC Driver for SQL Server

Schema discovery uses `INFORMATION_SCHEMA.TABLES`, `INFORMATION_SCHEMA.COLUMNS`, and `INFORMATION_SCHEMA.TABLE_CONSTRAINTS`.

### ODBC (source)

Uses the `odbc` PHP extension.

```php
Migrate::fromODBC(
    dsn:      'Driver={SQL Server};Server=localhost;Database=sourcedb',
    user:     'sa',
    password: 'secret'
);
```

**Requirements:** `ext-odbc`, appropriate ODBC driver

### PDO (generic) (source)

```php
Migrate::fromPDO(
    dsn:      'mysql:host=localhost;port=3306;dbname=sourcedb;charset=utf8mb4',
    user:     'root',
    password: 'secret',
    options:  [PDO::ATTR_TIMEOUT => 10]  // optional
);
```

**Requirements:** `ext-pdo` + appropriate PDO driver

---

## Target Engines (Writers)

All target methods accept an optional `$via` parameter to select the connection strategy:
- `WriterFactory::PDO` (default) — native PDO driver
- `WriterFactory::ODBC` — native ODBC via `ext-odbc` (no PDO)
- `WriterFactory::ODBCPDO` — ODBC bridged through PDO (`pdo_odbc`)

### MySQL (target)

```php
->toMySQL(
    host:        'localhost',
    user:        'root',
    password:    'secret',
    database:    'demodev',    // created automatically with utf8mb4 if not exists
    port:        3306,         // optional, default 3306
    via:         WriterFactory::PDO,  // optional (PDO | ODBC | ODBCPDO)
    extraParams: []            // optional (e.g. ['odbc_driver' => '...'])
)
```

- Tables created with `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4`
- Uses backtick identifier quoting
- `IF NOT EXISTS` in CREATE TABLE

### PostgreSQL (target)

```php
->toPgSQL(
    host:        'localhost',
    user:        'postgres',
    password:    'secret',
    database:    'demodev',    // created automatically if not exists
    port:        5432,         // optional, default 5432
    via:         WriterFactory::PDO,  // optional
    extraParams: []            // optional
)
```

- Uses double-quote identifier quoting
- `IF NOT EXISTS` in CREATE TABLE
- `BOOLEAN` is a native type

### Oracle (target)

```php
->toOCI(
    host:        'localhost',
    user:        'hr',
    password:    'secret',
    service:     'FREE',       // service name or SID
    port:        1521,         // optional, default 1521
    charset:     'AL32UTF8',   // optional, default 'AL32UTF8'
    via:         WriterFactory::PDO,  // optional
    extraParams: []            // optional
)
```

**Important:**
- Oracle schemas/databases cannot be created programmatically — the target user/schema must already exist.
- CREATE TABLE is wrapped in a PL/SQL block to handle "table already exists" gracefully.
- Date/timestamp columns use `TO_TIMESTAMP(?, 'YYYY-MM-DD HH24:MI:SS')` placeholders.

### SQL Server (target)

```php
->toSQLServer(
    host:        'localhost',
    user:        'sa',
    password:    'secret',
    database:    'demodev',    // created automatically if not exists
    port:        1433,         // optional, default 1433
    via:         WriterFactory::PDO,  // optional
    extraParams: []            // optional
)
```

**Requirements (PDO):** `ext-pdo_sqlsrv`, ODBC Driver 17 or 18 for SQL Server

- Uses `[bracket]` identifier quoting
- `TEXT` maps to `NVARCHAR(MAX)`, `BLOB` maps to `VARBINARY(MAX)`, `BOOLEAN` maps to `BIT`

### Firebird (target)

```php
->toFirebird(
    host:        'localhost',
    user:        'SYSDBA',
    password:    'masterkey',
    database:    '/path/to/TARGET.FDB',  // must already exist
    port:        3050,                   // optional, default 3050
    charset:     'UTF8',                 // optional, default 'UTF8'
    via:         WriterFactory::PDO,     // optional
    extraParams: []                      // optional
)
```

**Important:**
- Firebird database files must be pre-created with `gbak`, `isql`, or FlameRobin.
- DDL does not support `IF NOT EXISTS`; table existence is checked via `RDB$RELATIONS` first.
- `TEXT` maps to `BLOB SUB_TYPE TEXT`.
- `BOOLEAN` maps to `SMALLINT` (Firebird v2 compat; native `BOOLEAN` available in v3+).

### SQLite (target)

```php
->toSQLite(
    path: '/path/to/target.sqlite',  // created automatically if not exists
    via:  WriterFactory::PDO         // optional
)
```

- SQLite file is created automatically on first connection.
- Uses dynamic typing: `INTEGER`, `REAL`, `BLOB`, `TEXT`.
- No schema/user concept; parent directory is created if needed.

### PDO (generic) (target)

```php
->toPDO(
    engine:      'mysql',
    dsnTemplate: 'mysql:host=localhost;port=3306;dbname={dbname};charset=utf8mb4',
    database:    'demodev',
    params:      ['user' => 'root', 'password' => 'secret']
)
```

`{dbname}` in the DSN template is replaced with the `$database` argument.
Useful for custom drivers or DSN formats not covered by dedicated writers.

### ODBC (generic) (target)

```php
->toODBC(
    engine:   'access',
    database: 'mydb',
    params:   [
        'dsn'      => 'Driver={Microsoft Access Driver (*.mdb, *.accdb)};DBQ=C:\data\mydb.accdb;',
        'user'     => '',
        'password' => '',
    ]
)
```

Or build DSN automatically from components:

```php
->toODBC(
    engine:   'mysql',
    database: 'demodev',
    params:   [
        'odbc_driver' => 'MySQL ODBC 8.0 Unicode Driver',
        'host'        => 'localhost',
        'port'        => 3306,
        'user'        => 'root',
        'password'    => 'secret',
    ]
)
```

---

## Connection Strategies: PDO vs ODBC vs ODBCPDO

`WriterFactory::create(string $engine, string $via)` selects the writer class family:

```php
use GenericDatabase\Helpers\Migrators\Writers\WriterFactory;

// PDO (default) — uses pdo_mysql, pdo_pgsql, pdo_sqlsrv, etc.
->toMySQL('localhost', 'root', 'secret', 'demodev', 3306, WriterFactory::PDO)

// ODBC — native ext-odbc (odbc_connect), no PDO involved
->toMySQL('localhost', 'root', 'secret', 'demodev', 3306, WriterFactory::ODBC, [
    'odbc_driver' => 'MySQL ODBC 8.0 Unicode Driver',
])

// ODBCPDO — pdo_odbc (PDO wrapping an ODBC connection string)
->toMySQL('localhost', 'root', 'secret', 'demodev', 3306, WriterFactory::ODBCPDO, [
    'odbc_driver' => 'MySQL ODBC 8.0 Unicode Driver',
])
```

### Strategy routing table

| Engine | `WriterFactory::PDO` | `WriterFactory::ODBC` | `WriterFactory::ODBCPDO` |
|--------|---------------------|----------------------|--------------------------|
| `mysql` / `mariadb` | `PDO\MySQLPDOWriter` | `ODBC\MySQLODBCWriter` | `ODBCPDO\MySQLODBCPDOWriter` |
| `pgsql` / `postgres` | `PDO\PgSQLPDOWriter` | `ODBC\PgSQLODBCWriter` | `ODBCPDO\PgSQLODBCPDOWriter` |
| `oracle` / `oci` | `PDO\OCIPDOWriter` | `ODBC\OCIODBCWriter` | `ODBCPDO\OCIODBCPDOWriter` |
| `sqlsrv` / `mssql` | `PDO\SQLSrvPDOWriter` | `ODBC\SQLSrvODBCWriter` | `ODBCPDO\SQLSrvODBCPDOWriter` |
| `firebird` | `PDO\FirebirdPDOWriter` | `ODBC\FirebirdODBCWriter` | `ODBCPDO\FirebirdODBCPDOWriter` |
| `sqlite` | `PDO\SQLitePDOWriter` | `ODBC\SQLiteODBCWriter` | `ODBCPDO\SQLiteODBCPDOWriter` |
| other | `PDO\PDOWriter(engine, dsnTpl)` | `ODBC\ODBCWriter(engine)` | `ODBCPDO\ODBCPDOWriter(engine)` |

### Strategy comparison

| Feature | `PDO` | `ODBC` | `ODBCPDO` |
|---------|-------|--------|-----------|
| PHP dependency | `ext-pdo` + specific driver | `ext-odbc` | `ext-pdo` + `pdo_odbc` |
| Connection API | `new PDO(dsn, ...)` | `odbc_connect(dsn, ...)` | `new PDO("odbc:...", ...)` |
| Base class | `BasePDOWriter` | `BaseODBCWriter` | `BasePDOWriter` (extends PDO writer) |
| Error handling | PDO exceptions | Check `false` + `odbc_errormsg()` | PDO exceptions |
| DB auto-creation | Yes (where supported) | Yes (where supported) | Inherited from PDO writer |
| Return type | `BasePDOWriter` | `BaseODBCWriter` | `BasePDOWriter` |

### Native ODBC writer internals (`BaseODBCWriter`)

Native ODBC writers (`WriterFactory::ODBC`) extend `BaseODBCWriter` and use exclusively `ext-odbc` functions — no PDO at all.

```php
// Connection
$conn = odbc_connect($dsn, $user, $password);

// DDL
odbc_exec($conn, 'CREATE TABLE ...');

// DML — prepared statement
$stmt = odbc_prepare($conn, 'INSERT INTO "T" ("C1","C2") VALUES (?,?)');
odbc_execute($stmt, [$value1, $value2]);  // null stays null → SQL NULL
odbc_free_result($stmt);

// Cleanup
odbc_close($conn);
```

Database auto-creation per native ODBC strategy:

| Engine | Auto-create behaviour |
|--------|-----------------------|
| MySQL | Connects without `DATABASE=`; runs `CREATE DATABASE IF NOT EXISTS` |
| PostgreSQL | Connects to `postgres` maintenance DB with `odbc_autocommit(true)`; runs `CREATE DATABASE` |
| SQL Server | Connects to `master`; runs `IF NOT EXISTS CREATE DATABASE` |
| Oracle | No-op (schema/user must pre-exist) |
| Firebird | No-op (`.fdb` file must pre-exist) |
| SQLite | Creates parent directory with `mkdir()` |

### Default ODBC driver names

Used when `$params['odbc_driver']` is not supplied. Override per call via `$extraParams['odbc_driver']`.

| Engine | Default `odbc_driver` value |
|--------|---------------------------|
| MySQL | `MySQL ODBC 8.0 Unicode Driver` |
| PostgreSQL | `PostgreSQL Unicode` |
| Oracle | `Oracle in OraClient21Home1` |
| SQL Server | `ODBC Driver 18 for SQL Server` |
| Firebird | `Firebird/InterBase(r) driver` |
| SQLite | `SQLite3 ODBC Driver` |

---

## Fluent API Reference

### Factory methods (source selection)

| Method | Extension Required |
|--------|-------------------|
| `Migrate::fromSQLite(string $path)` | `ext-sqlite3` |
| `Migrate::fromMySQLi(host, user, pass, db, port, charset)` | `ext-mysqli` |
| `Migrate::fromPgSQL(host, user, pass, db, port)` | `ext-pgsql` |
| `Migrate::fromOCI(host, user, pass, db, port, charset)` | `ext-oci8` |
| `Migrate::fromFirebird(host, user, pass, db, port, charset)` | `ext-interbase` |
| `Migrate::fromSQLServer(host, user, pass, db, port)` | `ext-pdo_sqlsrv` or `pdo_odbc` |
| `Migrate::fromODBC(dsn, user, pass)` | `ext-odbc` |
| `Migrate::fromPDO(dsn, user, pass, options)` | `ext-pdo` |

### Migration methods (target selection)

| Method | Auto-creates DB | Notes |
|--------|----------------|-------|
| `->toMySQL(host, user, pass, db, port, via, extra)` | Yes | utf8mb4 charset |
| `->toPgSQL(host, user, pass, db, port, via, extra)` | Yes | |
| `->toOCI(host, user, pass, service, port, charset, via, extra)` | No | Schema must pre-exist |
| `->toSQLServer(host, user, pass, db, port, via, extra)` | Yes | Requires `pdo_sqlsrv` or ODBC driver |
| `->toFirebird(host, user, pass, db, port, charset, via, extra)` | No | File must pre-exist |
| `->toSQLite(path, via)` | Yes (file) | Creates file + directories |
| `->toPDO(engine, dsnTemplate, db, params)` | No | `{dbname}` placeholder |
| `->toODBC(engine, db, params)` | No | `params['dsn']` or `params['odbc_driver']` |

### Result methods

| Method | Returns |
|--------|---------|
| `->getMigrationResults()` | `array<string, MigrationResult>` keyed by engine |
| `->getMigrationResult(string $engine)` | `MigrationResult\|null` |
| `->getReader()` | `BaseMigrator` (access discovered tables and schema) |

---

## MigrationResult

Each `->toXxx()` call returns a `MigrationResult` object stored internally.

```php
$result = $migrate->getMigrationResult('mysql');

// Summary line: "sqlite -> mysql | demodev | 8 table(s) | 1234 row(s) | OK"
echo $result->getSummary();

// Per-table details
foreach ($result->getMigrated() as $table => $count) {
    echo "$table: $count rows migrated\n";
}

foreach ($result->getSkipped() as $table => $existing) {
    echo "$table: skipped ($existing rows already present)\n";
}

foreach ($result->getErrors() as $table => $msg) {
    echo "$table: ERROR - $msg\n";
}

// Global connection/database error (if any)
if ($result->getGlobalError()) {
    echo "Fatal: " . $result->getGlobalError() . "\n";
}

echo "Success: " . ($result->isSuccess() ? 'yes' : 'no') . "\n";
echo "Total records migrated: " . $result->getTotalMigratedRecords() . "\n";
```

---

## Type Mapping Reference

### Canonical Types

`BaseMigrator::normalizeToCanonical()` maps all source engine types to a common canonical set:

| Canonical | Source Examples |
|-----------|----------------|
| `integer` | `INT`, `INTEGER`, `INT UNSIGNED` |
| `bigint` | `BIGINT`, `BIGINT UNSIGNED` |
| `smallint` | `SMALLINT`, `TINYINT` |
| `boolean` | `BOOLEAN`, `BOOL`, `BIT` |
| `float` | `FLOAT`, `REAL` |
| `double` | `DOUBLE`, `DOUBLE PRECISION`, `BINARY_DOUBLE` |
| `decimal` | `DECIMAL`, `NUMERIC`, `NUMBER`, `MONEY` |
| `date` | `DATE` |
| `time` | `TIME` |
| `datetime` | `DATETIME`, `DATETIME2`, `SMALLDATETIME` |
| `timestamp` | `TIMESTAMP`, `TIMESTAMP(6)` |
| `text` | `TEXT`, `CLOB`, `NVARCHAR(MAX)` |
| `varchar` | `VARCHAR`, `VARCHAR2`, `NVARCHAR`, `CHARACTER VARYING` |
| `char` | `CHAR`, `NCHAR` |
| `blob` | `BLOB`, `BYTEA`, `VARBINARY` |
| `clob` | `CLOB`, `TEXT` |

### Target Type Conversion

#### → MySQL

| Canonical | MySQL Type |
|-----------|-----------|
| `integer` | `INT` |
| `bigint` | `BIGINT` |
| `smallint` | `SMALLINT` |
| `boolean` | `TINYINT(1)` |
| `float` | `FLOAT` |
| `double` | `DOUBLE` |
| `decimal` | `DECIMAL(size)` |
| `date` | `DATE` |
| `time` | `TIME` |
| `datetime`, `timestamp` | `DATETIME` |
| `blob` | `BLOB` |
| `text`, `clob` | `LONGTEXT` |
| `varchar` | `VARCHAR(size)` |
| `char` | `CHAR(size)` |

#### → PostgreSQL

| Canonical | PostgreSQL Type |
|-----------|----------------|
| `integer` | `INTEGER` |
| `bigint` | `BIGINT` |
| `smallint` | `SMALLINT` |
| `boolean` | `BOOLEAN` |
| `float` | `REAL` |
| `double` | `DOUBLE PRECISION` |
| `decimal` | `NUMERIC(size)` |
| `datetime`, `timestamp` | `TIMESTAMP` |
| `blob` | `BYTEA` |
| `text`, `clob` | `TEXT` |

#### → Oracle

| Canonical | Oracle Type |
|-----------|------------|
| `integer`, `bigint` | `NUMBER(10)` |
| `smallint` | `NUMBER(5)` |
| `boolean` | `NUMBER(1)` |
| `float` | `BINARY_FLOAT` |
| `double` | `BINARY_DOUBLE` |
| `decimal` | `NUMBER(size)` |
| `datetime`, `timestamp` | `TIMESTAMP` |
| `blob` | `BLOB` |
| `text`, `clob` | `CLOB` |
| `varchar` | `VARCHAR2(size)` |

#### → SQL Server

| Canonical | SQL Server Type |
|-----------|----------------|
| `integer` | `INT` |
| `bigint` | `BIGINT` |
| `smallint` | `SMALLINT` |
| `boolean` | `BIT` |
| `float`, `double` | `FLOAT` |
| `decimal` | `DECIMAL(size)` |
| `datetime`, `timestamp` | `DATETIME2` |
| `blob` | `VARBINARY(MAX)` |
| `text`, `clob` | `NVARCHAR(MAX)` |
| `varchar` | `NVARCHAR(size)` |
| `char` | `NCHAR(size)` |

#### → Firebird

| Canonical | Firebird Type |
|-----------|--------------|
| `integer` | `INTEGER` |
| `bigint` | `BIGINT` |
| `smallint`, `boolean` | `SMALLINT` |
| `float` | `FLOAT` |
| `double` | `DOUBLE PRECISION` |
| `decimal` | `DECIMAL(size)` |
| `datetime`, `timestamp` | `TIMESTAMP` |
| `blob` | `BLOB` |
| `text`, `clob` | `BLOB SUB_TYPE TEXT` |
| `varchar` | `VARCHAR(size)` |
| `char` | `CHAR(size)` |

#### → SQLite

| Canonical | SQLite Type |
|-----------|------------|
| `integer`, `bigint`, `smallint`, `boolean` | `INTEGER` |
| `float`, `double`, `decimal` | `REAL` |
| `blob` | `BLOB` |
| everything else | `TEXT` |

---

## Idempotency

The migrator is safe to run multiple times. Before inserting data into a table:

1. The table's existence is checked via `INFORMATION_SCHEMA` (or engine equivalent such as `RDB$RELATIONS` for Firebird, `sqlite_master` for SQLite, `USER_TABLES` for Oracle).
2. If the table exists and already has rows, the table is **skipped** (recorded in `MigrationResult::getSkipped()`).
3. If the table exists but is empty, data is inserted without re-creating the table.
4. If the table does not exist, it is created and then populated.

This makes the migration idempotent for full re-runs and safe to use in deployment pipelines.

---

## Oracle Special Handling

Oracle requires several adaptations beyond standard SQL:

### CREATE TABLE

Wrapped in PL/SQL to simulate `IF NOT EXISTS`:

```sql
BEGIN
  EXECUTE IMMEDIATE 'CREATE TABLE "USERS" (
    "ID" NUMBER(10) NOT NULL,
    "NAME" VARCHAR2(255),
    PRIMARY KEY ("ID")
  )';
EXCEPTION
  WHEN OTHERS THEN
    IF SQLCODE = -955 THEN NULL;  -- ORA-00955: table already exists
    ELSE RAISE;
    END IF;
END;
```

### Date / Timestamp Columns

INSERT placeholders use Oracle date functions:

```sql
INSERT INTO "USERS" ("ID", "NAME", "CREATED_AT")
VALUES (?, ?, TO_TIMESTAMP(?, 'YYYY-MM-DD HH24:MI:SS'))
```

Date values are normalised to `YYYY-MM-DD HH:MM:SS` format before binding.

### UTF-8 Coercion

All string values are verified/converted to UTF-8 before binding via `mb_check_encoding()` / `mb_convert_encoding()`.

### NLS Session

After connecting, the following NLS parameters are set:

```sql
ALTER SESSION SET NLS_DATE_FORMAT      = 'YYYY-MM-DD';
ALTER SESSION SET NLS_TIMESTAMP_FORMAT = 'YYYY-MM-DD HH24:MI:SS';
ALTER SESSION SET NLS_LANGUAGE         = 'AMERICAN';
ALTER SESSION SET NLS_TERRITORY        = 'AMERICA';
```

---

## Firebird Special Handling

- **No `IF NOT EXISTS`** in DDL — table existence is checked via `RDB$RELATIONS` before executing `CREATE TABLE`.
- **Identifier quoting** uses double quotes with UPPERCASE names (Firebird convention).
- **`BLOB SUB_TYPE TEXT`** is used for `TEXT`/`CLOB` columns.
- **`SMALLINT`** is used for `BOOLEAN` (native `BOOLEAN` requires Firebird 3+).
- **Database files** must be pre-created with external tools (gbak, isql, FlameRobin, etc.).

---

## Extending the System

### Custom Source Reader

```php
namespace MyApp\Migrators;

use GenericDatabase\Helpers\Migrators\Readers\BaseMigrator;

class MyCustomReader extends BaseMigrator
{
    public function __construct(string $connectionString)
    {
        // connect …
        $this->loadTables();
        $this->loadTableSchemas();
    }

    public function getSourceEngine(): string { return 'mycustom'; }

    protected function loadTables(): void
    {
        // populate $this->tables = ['table1', 'table2', …]
    }

    protected function loadTableSchemas(): void
    {
        // populate $this->tableSchemas[$table] = [
        //     'columns' => [
        //         ['name' => 'id', 'raw_type' => 'INT', 'canonical' => 'integer',
        //          'notnull' => true, 'dflt_value' => null, 'pk' => true],
        //         …
        //     ],
        //     'primaryKey' => 'id',
        // ]
    }

    public function getTableData(string $table): array
    {
        // return array of associative arrays (one per row)
    }
}
```

### Custom PDO Target Writer

```php
namespace MyApp\Migrators;

use GenericDatabase\Helpers\Migrators\Writers\BasePDOWriter;
use PDO;

class MyTargetWriter extends BasePDOWriter
{
    public function getTargetEngine(): string { return 'mytarget'; }

    public function escapeIdentifier(string $name): string
    {
        return "`$name`";
    }

    protected function convertType(string $canonical, string $rawType): string
    {
        return match ($canonical) {
            'integer' => 'INT',
            'text'    => 'TEXT',
            default   => 'VARCHAR(255)',
        };
    }

    protected function createDatabaseIfNotExists(string $dbName, array $params): void
    {
        // create DB or no-op
    }

    protected function createConnection(string $dbName, array $params): PDO
    {
        return new PDO(
            "mytarget:host={$params['host']};dbname=$dbName",
            $params['user'],
            $params['password']
        );
    }
}
```

### Custom Native ODBC Target Writer

```php
namespace MyApp\Migrators;

use GenericDatabase\Helpers\Migrators\Writers\BaseODBCWriter;

class MyODBCTargetWriter extends BaseODBCWriter
{
    public function getTargetEngine(): string { return 'mytarget'; }

    public function escapeIdentifier(string $name): string
    {
        return '"' . str_replace('"', '""', $name) . '"';
    }

    protected function convertType(string $canonical, string $rawType): string
    {
        return match ($canonical) {
            'integer' => 'INT',
            'text'    => 'TEXT',
            default   => 'VARCHAR(255)',
        };
    }

    protected function createDatabaseIfNotExists(string $dbName, array $params): void
    {
        // no-op or custom logic
    }

    protected function createODBCConnection(string $dbName, array $params): mixed
    {
        $driver = $params['odbc_driver'] ?? 'My Custom Driver';
        $dsn    = "Driver={$driver};Server={$params['host']};Database=$dbName;";
        return odbc_connect($dsn, $params['user'], $params['password']);
    }
}
```

### Use Custom Reader/Writer with Migrate

```php
// Call writer directly
$reader = new MyCustomReader($connectionString);
$writer = new MyTargetWriter();
$result = $writer->migrate($reader, 'target_db', [
    'host'     => 'localhost',
    'user'     => 'user',
    'password' => 'secret',
]);
```

---

## Comparison with Standalone Script

| Feature | `migrate-sqlite-to-databases.php` | `migrate-sqlite-to-databases-oop.php` + Migrators |
|---------|-----------------------------------|--------------------------------------------------|
| Dependencies | None (standalone) | `vendor/autoload.php` + Migrators classes |
| Source | SQLite only | Any supported engine |
| Targets | MySQL, PostgreSQL, Oracle, SQL Server | Any supported engine (extensible) |
| PDO support | Yes (hardcoded) | Yes — `WriterFactory::PDO` (default) |
| Native ODBC support | No | Yes — `WriterFactory::ODBC` (`ext-odbc`) |
| ODBC via PDO support | No | Yes — `WriterFactory::ODBCPDO` (`pdo_odbc`) |
| Type conversion | Hardcoded per-engine functions | Canonical type system (reusable) |
| Idempotency | Yes | Yes |
| Results | Console echo only | `MigrationResult` objects + console |
| Reuse | Not reusable | Fully reusable as a library |
| Custom targets | No | Yes — implement `BasePDOWriter` or `BaseODBCWriter` |

The standalone script (`migrate-sqlite-to-databases.php`) remains fully functional with zero dependencies and is the recommended option for one-off migrations. The OOP API is the recommended option when migrations need to be automated, tested, or integrated into application code.

---

## File Structure

```
src/Helpers/Migrators/
│
├── Migrate.php              # Orchestrator (fluent factory + migration methods)
├── MigrationResult.php      # Result tracking per target engine
│
├── Readers/
│   ├── BaseMigrator.php     # Abstract source reader + normalizeToCanonical()
│   ├── SQLiteReader.php     # SQLite source (ext-sqlite3, read-only)
│   ├── MySQLiReader.php     # MySQL/MariaDB source (ext-mysqli)
│   ├── PgSQLReader.php      # PostgreSQL source (ext-pgsql)
│   ├── OCIReader.php        # Oracle source (ext-oci8, lowercase identifiers)
│   ├── FirebirdReader.php   # Firebird source (ext-interbase, field-type code map)
│   ├── SQLSrvReader.php     # SQL Server source (pdo_sqlsrv or pdo_odbc fallback)
│   ├── ODBCReader.php       # ODBC source (ext-odbc)
│   └── PDOReader.php        # Generic PDO source
│
└── Writers/
    ├── BasePDOWriter.php     # Abstract PDO target writer (migration template)
    ├── BaseODBCWriter.php    # Abstract native ODBC target writer (ext-odbc, no PDO)
    ├── WriterFactory.php     # Strategy selector: PDO | ODBC | ODBCPDO
    │
    ├── PDO/                  # Native PDO writers (WriterFactory::PDO — default)
    │   ├── MySQLPDOWriter.php
    │   ├── PgSQLPDOWriter.php
    │   ├── OCIPDOWriter.php      # PL/SQL CREATE TABLE + TO_TIMESTAMP + UTF-8 + NLS
    │   ├── SQLSrvPDOWriter.php
    │   ├── FirebirdPDOWriter.php # No IF NOT EXISTS; tableExists via RDB$RELATIONS
    │   ├── SQLitePDOWriter.php
    │   └── PDOWriter.php         # Generic PDO target (custom DSN template)
    │
    ├── ODBC/                 # Native ODBC writers (WriterFactory::ODBC — ext-odbc)
    │   ├── MySQLODBCWriter.php
    │   ├── PgSQLODBCWriter.php
    │   ├── OCIODBCWriter.php     # tableExists via USER_TABLES; TO_TIMESTAMP placeholders
    │   ├── SQLSrvODBCWriter.php
    │   ├── FirebirdODBCWriter.php  # tableExists via RDB$RELATIONS; no IF NOT EXISTS
    │   ├── SQLiteODBCWriter.php    # tableExists via sqlite_master
    │   └── ODBCWriter.php          # Generic native ODBC target (raw DSN or params)
    │
    └── ODBCPDO/              # PDO-over-ODBC writers (WriterFactory::ODBCPDO)
        ├── MySQLODBCPDOWriter.php
        ├── PgSQLODBCPDOWriter.php
        ├── OCIODBCPDOWriter.php
        ├── SQLSrvODBCPDOWriter.php
        ├── FirebirdODBCPDOWriter.php
        ├── SQLiteODBCPDOWriter.php
        └── ODBCPDOWriter.php       # Generic pdo_odbc target

.devtools/.source/
├── migrate-sqlite-to-databases.php      # Standalone script (no dependencies)
└── migrate-sqlite-to-databases-oop.php  # OOP script using Migrators API
```
