<?php

namespace GenericDatabase\Helpers\Exporters;

use Exception;

/**
 * Export Class
 * Central class to orchestrate database exports to different formats
 *
 * Usage:
 * Export::fromSQLite('path/to/database.db')
 *     ->toCSV('path/to/output')
 *     ->toXML('path/to/output')
 *     ->toJSON('path/to/output')
 *     ->toYAML('path/to/output')
 *     ->toINI('path/to/output')
 *     ->toNEON('path/to/output');
 *
 * Export::fromMySQLi($host, $user, $password, $database, $outputPath)
 *     ->toCSV('path/to/output')
 *     ->toJSON('path/to/output');
 */
class Export
{
    private ?BaseExporter $engineExporter = null;
    private array $exportedPaths = [];

    /**
     * Private constructor - use static factory methods
     *
     * @param BaseExporter $engineExporter Engine-specific exporter instance
     */
    private function __construct(BaseExporter $engineExporter)
    {
        $this->engineExporter = $engineExporter;
    }

    /**
     * Create new Export instance from SQLite database
     *
     * @param string $databasePath Path to SQLite database file
     * @param string $outputPath Path where exported files will be saved
     * @return self
     */
    public static function fromSQLite(string $databasePath, string $outputPath): self
    {
        return new self(new SQLiteExporter($databasePath, $outputPath));
    }

    /**
     * Create new Export instance from MySQL database using MySQLi
     *
     * @param string $host Database host
     * @param string $user Database user
     * @param string $password Database password
     * @param string $database Database name
     * @param string $outputPath Path where exported files will be saved
     * @param int $port Database port (default: 3306)
     * @param string $charset Database charset (default: utf8)
     * @return self
     */
    public static function fromMySQLi(
        string $host,
        string $user,
        string $password,
        string $database,
        string $outputPath,
        int $port = 3306,
        string $charset = 'utf8'
    ): self {
        return new self(new MySQLiExporter($host, $user, $password, $database, $outputPath, $port, $charset));
    }

    /**
     * Create new Export instance from PostgreSQL database
     *
     * @param string $host Database host
     * @param string $user Database user
     * @param string $password Database password
     * @param string $database Database name
     * @param string $outputPath Path where exported files will be saved
     * @param int $port Database port (default: 5432)
     * @return self
     */
    public static function fromPgSQL(
        string $host,
        string $user,
        string $password,
        string $database,
        string $outputPath,
        int $port = 5432
    ): self {
        return new self(new PgSQLExporter($host, $user, $password, $database, $outputPath, $port));
    }

    /**
     * Create new Export instance from Oracle database using OCI8
     *
     * @param string $host Database host
     * @param string $user Database user
     * @param string $password Database password
     * @param string $database Database name (SID or Service Name)
     * @param string $outputPath Path where exported files will be saved
     * @param int $port Database port (default: 1521)
     * @param string $charset Database charset (default: AL32UTF8)
     * @return self
     */
    public static function fromOCI(
        string $host,
        string $user,
        string $password,
        string $database,
        string $outputPath,
        int $port = 1521,
        string $charset = 'AL32UTF8'
    ): self {
        return new self(new OCIExporter($host, $user, $password, $database, $outputPath, $port, $charset));
    }

    /**
     * Create new Export instance from Firebird/Interbase database
     *
     * @param string $host Database host
     * @param string $user Database user
     * @param string $password Database password
     * @param string $database Database path or alias
     * @param string $outputPath Path where exported files will be saved
     * @param int $port Database port (default: 3050)
     * @param string $charset Database charset (default: UTF8)
     * @return self
     */
    public static function fromFirebird(
        string $host,
        string $user,
        string $password,
        string $database,
        string $outputPath,
        int $port = 3050,
        string $charset = 'UTF8'
    ): self {
        return new self(new FirebirdExporter($host, $user, $password, $database, $outputPath, $port, $charset));
    }

    /**
     * Create new Export instance from database using PDO
     *
     * @param string $driver PDO driver (mysql, pgsql, sqlite, oci, sqlsrv, etc.)
     * @param string $dsn Data Source Name
     * @param string $user Database user
     * @param string $password Database password
     * @param string $outputPath Path where exported files will be saved
     * @param array $options PDO options
     * @return self
     */
    public static function fromPDO(
        string $driver,
        string $dsn,
        string $user,
        string $password,
        string $outputPath,
        array $options = []
    ): self {
        return new self(new PDOExporter($driver, $dsn, $user, $password, $outputPath, $options));
    }

    /**
     * Create new Export instance from database using ODBC
     *
     * @param string $driver ODBC driver name
     * @param string $dsn Data Source Name or connection string
     * @param string $user Database user
     * @param string $password Database password
     * @param string $outputPath Path where exported files will be saved
     * @return self
     */
    public static function fromODBC(
        string $driver,
        string $dsn,
        string $user,
        string $password,
        string $outputPath
    ): self {
        return new self(new ODBCExporter($driver, $dsn, $user, $password, $outputPath));
    }

    /**
     * Export to CSV format
     *
     * @param string $outputPath Path where CSV files will be saved
     * @param string $delimiter CSV delimiter (default: ;)
     * @return self
     * @throws Exception
     */
    public function toCSV(string $outputPath, string $delimiter = ';'): self
    {
        $outputPath = rtrim($outputPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!is_dir($outputPath)) {
            mkdir($outputPath, 0755, true);
        }

        // Update exporter's output path temporarily
        $originalPath = $this->engineExporter->getOutputPath();
        $this->setExporterOutputPath($outputPath);

        $formatExporter = new CSVExporter($this->engineExporter);
        $formatExporter->setDelimiter($delimiter);

        $files = $formatExporter->export();
        $this->exportedPaths['csv'] = $files;

        // Generate Schema.ini
        $schemaGenerator = new SchemaGenerator(
            $outputPath,
            $this->engineExporter->getTables(),
            $this->engineExporter->getTableSchemas(),
            'csv'
        );
        $schemaGenerator->generate($this->engineExporter);

        // Restore original path
        $this->setExporterOutputPath($originalPath);

        return $this;
    }

    /**
     * Export to XML format
     *
     * @param string $outputPath Path where XML files will be saved
     * @return self
     * @throws Exception
     */
    public function toXML(string $outputPath): self
    {
        $outputPath = rtrim($outputPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!is_dir($outputPath)) {
            mkdir($outputPath, 0755, true);
        }

        $originalPath = $this->engineExporter->getOutputPath();
        $this->setExporterOutputPath($outputPath);

        $formatExporter = new XMLExporter($this->engineExporter);

        $files = $formatExporter->export();
        $this->exportedPaths['xml'] = $files;

        // Generate Schema.ini
        $schemaGenerator = new SchemaGenerator(
            $outputPath,
            $this->engineExporter->getTables(),
            $this->engineExporter->getTableSchemas(),
            'xml'
        );
        $schemaGenerator->generate($this->engineExporter);

        $this->setExporterOutputPath($originalPath);

        return $this;
    }

    /**
     * Export to JSON format
     *
     * @param string $outputPath Path where JSON files will be saved
     * @return self
     * @throws Exception
     */
    public function toJSON(string $outputPath): self
    {
        $outputPath = rtrim($outputPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!is_dir($outputPath)) {
            mkdir($outputPath, 0755, true);
        }

        $originalPath = $this->engineExporter->getOutputPath();
        $this->setExporterOutputPath($outputPath);

        $formatExporter = new JSONExporter($this->engineExporter);

        $files = $formatExporter->export();
        $this->exportedPaths['json'] = $files;

        // Generate Schema.ini
        $schemaGenerator = new SchemaGenerator(
            $outputPath,
            $this->engineExporter->getTables(),
            $this->engineExporter->getTableSchemas(),
            'json'
        );
        $schemaGenerator->generate($this->engineExporter);

        $this->setExporterOutputPath($originalPath);

        return $this;
    }

    /**
     * Export to YAML format
     *
     * @param string $outputPath Path where YAML files will be saved
     * @return self
     * @throws Exception
     */
    public function toYAML(string $outputPath): self
    {
        $outputPath = rtrim($outputPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!is_dir($outputPath)) {
            mkdir($outputPath, 0755, true);
        }

        $originalPath = $this->engineExporter->getOutputPath();
        $this->setExporterOutputPath($outputPath);

        $formatExporter = new YAMLExporter($this->engineExporter);

        $files = $formatExporter->export();
        $this->exportedPaths['yaml'] = $files;

        // Generate Schema.ini
        $schemaGenerator = new SchemaGenerator(
            $outputPath,
            $this->engineExporter->getTables(),
            $this->engineExporter->getTableSchemas(),
            'yaml'
        );
        $schemaGenerator->generate($this->engineExporter);

        $this->setExporterOutputPath($originalPath);

        return $this;
    }

    /**
     * Export to INI format
     *
     * @param string $outputPath Path where INI files will be saved
     * @return self
     * @throws Exception
     */
    public function toINI(string $outputPath): self
    {
        $outputPath = rtrim($outputPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!is_dir($outputPath)) {
            mkdir($outputPath, 0755, true);
        }

        $originalPath = $this->engineExporter->getOutputPath();
        $this->setExporterOutputPath($outputPath);

        $formatExporter = new INIExporter($this->engineExporter);

        $files = $formatExporter->export();
        $this->exportedPaths['ini'] = $files;

        // Generate Schema.ini
        $schemaGenerator = new SchemaGenerator(
            $outputPath,
            $this->engineExporter->getTables(),
            $this->engineExporter->getTableSchemas(),
            'ini'
        );
        $schemaGenerator->generate($this->engineExporter);

        $this->setExporterOutputPath($originalPath);

        return $this;
    }

    /**
     * Export to NEON format
     *
     * @param string $outputPath Path where NEON files will be saved
     * @return self
     * @throws Exception
     */
    public function toNEON(string $outputPath): self
    {
        $outputPath = rtrim($outputPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!is_dir($outputPath)) {
            mkdir($outputPath, 0755, true);
        }

        $originalPath = $this->engineExporter->getOutputPath();
        $this->setExporterOutputPath($outputPath);

        $formatExporter = new NEONExporter($this->engineExporter);

        $files = $formatExporter->export();
        $this->exportedPaths['neon'] = $files;

        // Generate Schema.ini
        $schemaGenerator = new SchemaGenerator(
            $outputPath,
            $this->engineExporter->getTables(),
            $this->engineExporter->getTableSchemas(),
            'neon'
        );
        $schemaGenerator->generate($this->engineExporter);

        $this->setExporterOutputPath($originalPath);

        return $this;
    }

    /**
     * Set exporter output path
     *
     * @param string $outputPath New output path
     * @return void
     */
    private function setExporterOutputPath(string $outputPath): void
    {
        $this->engineExporter->setOutputPath($outputPath);
    }

    /**
     * Get exported file paths
     *
     * @return array Array of exported file paths by format
     */
    public function getExportedPaths(): array
    {
        return $this->exportedPaths;
    }

    /**
     * Get exported files for a specific format
     *
     * @param string $format Format (csv, xml, json, yaml, ini, neon)
     * @return array Array of file paths
     */
    public function getExportedFiles(string $format): array
    {
        return $this->exportedPaths[$format] ?? [];
    }
}
