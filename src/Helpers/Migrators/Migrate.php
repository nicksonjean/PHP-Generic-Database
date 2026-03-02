<?php

namespace GenericDatabase\Helpers\Migrators;

use GenericDatabase\Helpers\Migrators\Readers\BaseMigrator;
use GenericDatabase\Helpers\Migrators\Readers\SQLiteReader;
use GenericDatabase\Helpers\Migrators\Readers\MySQLiReader;
use GenericDatabase\Helpers\Migrators\Readers\PgSQLReader;
use GenericDatabase\Helpers\Migrators\Readers\OCIReader;
use GenericDatabase\Helpers\Migrators\Readers\FirebirdReader;
use GenericDatabase\Helpers\Migrators\Readers\ODBCReader;
use GenericDatabase\Helpers\Migrators\Readers\PDOReader;
use GenericDatabase\Helpers\Migrators\Readers\SQLSrvReader;
use GenericDatabase\Helpers\Migrators\Writers\WriterFactory;
use Exception;

/**
 * Migrate — Central orchestrator for database-to-database migrations.
 *
 * Fluent interface: chain source factory → target method(s) → inspect results.
 *
 * Three connection strategies are supported via the $via parameter:
 *   WriterFactory::PDO      (default) — native PDO drivers
 *   WriterFactory::ODBC               — native ODBC via ext-odbc (odbc_connect)
 *   WriterFactory::ODBCPDO            — ODBC via PDO over ODBC (PDO with an ODBC DSN string)
 *
 * Usage (SQLite → multiple targets, native PDO — $via is optional):
 *
 * ```php
 * $migrate = Migrate::fromSQLite('/path/to/DB.SQLITE')
 *     ->toMySQL('localhost', 'root', 'secret', 'demodev', 3306)
 *     ->toPgSQL('localhost', 'postgres', 'secret', 'demodev', 5432)
 *     ->toOCI('localhost', 'hr', 'secret', 'FREE', 1521)
 *     ->toSQLServer('localhost', 'sa', 'secret', 'demodev', 1433)
 *     ->toFirebird('localhost', 'SYSDBA', 'masterkey', '/path/to/DB.FDB', 3050)
 *     ->toSQLite('/path/to/target.sqlite');
 * ```
 *
 * Usage (SQLite → MySQL via native ODBC, ext-odbc):
 *
 * ```php
 * $migrate = Migrate::fromSQLite('/path/to/DB.SQLITE')
 *     ->toMySQL('localhost', 'root', 'secret', 'demodev', 3306, WriterFactory::ODBC, [
 *         'odbc_driver' => 'MySQL ODBC 8.0 Unicode Driver',
 *     ]);
 * ```
 *
 * Usage (SQLite → MySQL via PDO over ODBC (PDO with an ODBC DSN string)):
 *
 * ```php
 * $migrate = Migrate::fromSQLite('/path/to/DB.SQLITE')
 *     ->toMySQL('localhost', 'root', 'secret', 'demodev', 3306, WriterFactory::ODBCPDO, [
 *         'odbc_driver' => 'MySQL ODBC 8.0 Unicode Driver',
 *     ]);
 * ```
 *
 * Usage (MySQL → PostgreSQL):
 *
 * ```php
 * $migrate = Migrate::fromMySQLi('localhost', 'root', 'secret', 'sourcedb')
 *     ->toPgSQL('localhost', 'postgres', 'secret', 'targetdb');
 * ```
 */
class Migrate
{
    private BaseMigrator $reader;

    /** @var array<string, MigrationResult> engine => result */
    private array $results = [];

    private function __construct(BaseMigrator $reader)
    {
        $this->reader = $reader;
    }

    // -----------------------------------------------------------------------
    // Source factory methods
    // -----------------------------------------------------------------------

    /**
     * Read from a SQLite database file.
     *
     * @param string $databasePath Absolute path to the .sqlite / .db file
     * @throws Exception
     */
    public static function fromSQLite(string $databasePath): self
    {
        return new self(new SQLiteReader($databasePath));
    }

    /**
     * Read from a MySQL / MariaDB database using the MySQLi extension.
     *
     * @throws Exception
     */
    public static function fromMySQLi(
        string $host,
        string $user,
        string $password,
        string $database,
        int $port = 3306,
        string $charset = 'utf8mb4'
    ): self {
        return new self(new MySQLiReader($host, $user, $password, $database, $port, $charset));
    }

    /**
     * Read from a PostgreSQL database using the pgsql extension.
     *
     * @throws Exception
     */
    public static function fromPgSQL(
        string $host,
        string $user,
        string $password,
        string $database,
        int $port = 5432
    ): self {
        return new self(new PgSQLReader($host, $user, $password, $database, $port));
    }

    /**
     * Read from an Oracle database using the OCI8 extension.
     *
     * @param string $database Service name or SID (e.g. "FREE", "ORCL")
     * @throws Exception
     */
    public static function fromOCI(
        string $host,
        string $user,
        string $password,
        string $database,
        int $port = 1521,
        string $charset = 'AL32UTF8'
    ): self {
        return new self(new OCIReader($host, $user, $password, $database, $port, $charset));
    }

    /**
     * Read from a Firebird database using the ibase extension.
     *
     * @param string $database Path to the .fdb file or alias
     * @throws Exception
     */
    public static function fromFirebird(
        string $host,
        string $user,
        string $password,
        string $database,
        int $port = 3050,
        string $charset = 'UTF8'
    ): self {
        return new self(new FirebirdReader($host, $user, $password, $database, $port, $charset));
    }

    /**
     * Read from an ODBC data source.
     *
     * @param string $dsn Full ODBC connection string or registered DSN name
     * @throws Exception
     */
    public static function fromODBC(
        string $dsn,
        string $user = '',
        string $password = ''
    ): self {
        return new self(new ODBCReader($dsn, $user, $password));
    }

    /**
     * Read from a Microsoft SQL Server database (via pdo_sqlsrv or pdo_odbc fallback).
     *
     * @throws Exception
     */
    public static function fromSQLServer(
        string $host,
        string $user,
        string $password,
        string $database,
        int $port = 1433
    ): self {
        return new self(new SQLSrvReader($host, $user, $password, $database, $port));
    }

    /**
     * Read from any PDO-compatible data source.
     *
     * @param string               $dsn     Full PDO DSN
     * @param array<string, mixed> $options PDO options
     * @throws Exception
     */
    public static function fromPDO(
        string $dsn,
        string $user = '',
        string $password = '',
        array $options = []
    ): self {
        return new self(new PDOReader($dsn, $user, $password, $options));
    }

    // -----------------------------------------------------------------------
    // Target migration methods (fluent)
    // -----------------------------------------------------------------------

    /**
     * Migrate to a MySQL / MariaDB database.
     *
     * @param string               $host       MySQL host
     * @param string               $user       MySQL user
     * @param string               $password   MySQL password
     * @param string               $database   Target database name (created if not exists)
     * @param int                  $port       Default 3306
     * @param string               $via        WriterFactory::PDO (default), ::ODBC, or ::ODBCPDO
     * @param array<string, mixed> $extraParams Additional params (e.g. 'odbc_driver')
     */
    public function toMySQL(
        string $host,
        string $user,
        string $password,
        string $database,
        int $port = 3306,
        string $via = WriterFactory::PDO,
        array $extraParams = []
    ): self {
        $writer = WriterFactory::create('mysql', $via);
        $this->results['mysql'] = $writer->migrate($this->reader, $database, array_merge([
            'host'     => $host,
            'user'     => $user,
            'password' => $password,
            'port'     => $port,
        ], $extraParams));
        return $this;
    }

    /**
     * Migrate to a PostgreSQL database.
     *
     * @param string               $host       PostgreSQL host
     * @param string               $user       PostgreSQL user
     * @param string               $password   PostgreSQL password
     * @param string               $database   Target database name (created if not exists)
     * @param int                  $port       Default 5432
     * @param string               $via        WriterFactory::PDO (default), ::ODBC, or ::ODBCPDO
     * @param array<string, mixed> $extraParams Additional params (e.g. 'odbc_driver')
     */
    public function toPgSQL(
        string $host,
        string $user,
        string $password,
        string $database,
        int $port = 5432,
        string $via = WriterFactory::PDO,
        array $extraParams = []
    ): self {
        $writer = WriterFactory::create('pgsql', $via);
        $this->results['pgsql'] = $writer->migrate($this->reader, $database, array_merge([
            'host'     => $host,
            'user'     => $user,
            'password' => $password,
            'port'     => $port,
        ], $extraParams));
        return $this;
    }

    /**
     * Migrate to an Oracle schema.
     *
     * Note: Oracle databases/schemas cannot be created programmatically via SQL.
     * The target user/schema must already exist.
     *
     * @param string               $host       Oracle host
     * @param string               $user       Oracle schema/user
     * @param string               $password   Password
     * @param string               $service    Service name or SID (e.g. "FREE", "ORCL")
     * @param int                  $port       Default 1521
     * @param string               $charset    NLS character set (default AL32UTF8)
     * @param string               $via        WriterFactory::PDO (default), ::ODBC, or ::ODBCPDO
     * @param array<string, mixed> $extraParams Additional params (e.g. 'odbc_driver')
     */
    public function toOCI(
        string $host,
        string $user,
        string $password,
        string $service,
        int $port = 1521,
        string $charset = 'AL32UTF8',
        string $via = WriterFactory::PDO,
        array $extraParams = []
    ): self {
        $writer = WriterFactory::create('oracle', $via);
        $this->results['oracle'] = $writer->migrate($this->reader, $service, array_merge([
            'host'     => $host,
            'user'     => $user,
            'password' => $password,
            'port'     => $port,
            'service'  => $service,
            'charset'  => $charset,
        ], $extraParams));
        return $this;
    }

    /**
     * Migrate to a Microsoft SQL Server database.
     *
     * @param string               $host       SQL Server host
     * @param string               $user       SQL Server login
     * @param string               $password   Password
     * @param string               $database   Target database name (created if not exists)
     * @param int                  $port       Default 1433
     * @param string               $via        WriterFactory::PDO (default), ::ODBC, or ::ODBCPDO
     * @param array<string, mixed> $extraParams Additional params (e.g. 'odbc_driver')
     */
    public function toSQLServer(
        string $host,
        string $user,
        string $password,
        string $database,
        int $port = 1433,
        string $via = WriterFactory::PDO,
        array $extraParams = []
    ): self {
        $writer = WriterFactory::create('sqlsrv', $via);
        $this->results['sqlsrv'] = $writer->migrate($this->reader, $database, array_merge([
            'host'     => $host,
            'user'     => $user,
            'password' => $password,
            'port'     => $port,
        ], $extraParams));
        return $this;
    }

    /**
     * Migrate to a Firebird database.
     *
     * Note: Firebird database files (.fdb) cannot be created via SQL.
     * The target database file must already exist.
     *
     * @param string               $host       Firebird host
     * @param string               $user       Firebird user
     * @param string               $password   Password
     * @param string               $database   Path to the .fdb file or alias
     * @param int                  $port       Default 3050
     * @param string               $charset    Character set (default UTF8)
     * @param string               $via        WriterFactory::PDO (default), ::ODBC, or ::ODBCPDO
     * @param array<string, mixed> $extraParams Additional params (e.g. 'odbc_driver')
     */
    public function toFirebird(
        string $host,
        string $user,
        string $password,
        string $database,
        int $port = 3050,
        string $charset = 'UTF8',
        string $via = WriterFactory::PDO,
        array $extraParams = []
    ): self {
        $writer = WriterFactory::create('firebird', $via);
        $this->results['firebird'] = $writer->migrate($this->reader, $database, array_merge([
            'host'     => $host,
            'user'     => $user,
            'password' => $password,
            'port'     => $port,
            'database' => $database,
            'charset'  => $charset,
        ], $extraParams));
        return $this;
    }

    /**
     * Migrate to a SQLite database file.
     *
     * @param string $path Absolute path to the target SQLite file (created if not exists)
     * @param string $via  WriterFactory::PDO (default) or WriterFactory::ODBC
     */
    public function toSQLite(
        string $path,
        string $via = WriterFactory::PDO
    ): self {
        $writer = WriterFactory::create('sqlite', $via);
        $this->results['sqlite'] = $writer->migrate($this->reader, $path, [
            'database' => $path,
        ]);
        return $this;
    }

    /**
     * Migrate via a generic PDO connection defined by a DSN template.
     *
     * @param string               $engine      Logical engine name used as result key
     * @param string               $dsnTemplate DSN with {dbname} placeholder
     * @param string               $database    Target database / schema name
     * @param array<string, mixed> $params      Connection params (user, password, options, …)
     */
    public function toPDO(
        string $engine,
        string $dsnTemplate,
        string $database,
        array $params = []
    ): self {
        $writer = WriterFactory::create($engine, WriterFactory::PDO, $dsnTemplate);
        $this->results[$engine] = $writer->migrate($this->reader, $database, $params);
        return $this;
    }

    /**
     * Migrate via a generic ODBC connection.
     *
     * @param string               $engine      Logical engine name used as result key
     * @param string               $database    Target database / schema name
     * @param array<string, mixed> $params      Connection params (user, password, dsn or odbc_driver, …)
     */
    public function toODBC(
        string $engine,
        string $database,
        array $params = []
    ): self {
        $writer = WriterFactory::create($engine, WriterFactory::ODBC);
        $this->results[$engine] = $writer->migrate($this->reader, $database, $params);
        return $this;
    }

    // -----------------------------------------------------------------------
    // Result accessors
    // -----------------------------------------------------------------------

    /**
     * Return all migration results keyed by target engine name.
     *
     * @return array<string, MigrationResult>
     */
    public function getMigrationResults(): array
    {
        return $this->results;
    }

    /**
     * Return the migration result for a specific target engine.
     *
     * @param string $engine Engine key ('mysql', 'pgsql', 'oracle', 'sqlsrv', 'firebird', 'sqlite')
     */
    public function getMigrationResult(string $engine): ?MigrationResult
    {
        return $this->results[$engine] ?? null;
    }

    /**
     * Return the source reader instance (useful for inspecting discovered tables).
     */
    public function getReader(): BaseMigrator
    {
        return $this->reader;
    }
}
