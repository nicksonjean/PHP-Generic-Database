<?php

namespace GenericDatabase\Engine\CSV\Connection\Structure;

use AllowDynamicProperties;
use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Shared\Run;
use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Helpers\Parsers\Schema;
use GenericDatabase\Interfaces\Connection\IStructure;
use GenericDatabase\Generic\Connection\Structure;
use GenericDatabase\Engine\CSV\Connection\CSV;
use GenericDatabase\Interfaces\Connection\IStructureStrategy;
use GenericDatabase\Engine\CSV\Connection\Structure\Strategy\StructureStrategy;

#[AllowDynamicProperties]
class StructureHandler implements IStructure
{
    protected static IConnection $instance;

    /**
     * @var self|null Current handler instance.
     */
    private static ?self $self = null;

    /**
     * Database directory path (folder containing CSV files) or 'memory' for in-memory database
     * @var string $database
     */
    private static string $database = '';

    /**
     * Available tables (CSV files in the database folder)
     * @var array $tables
     */
    private static array $tables = [];

    /**
     * Schema definition (loaded from Schema.ini if exists)
     * @var Structure|null $schema
     */
    private static ?Structure $schema = null;

    /**
     * Current active table
     * @var string|null $currentTable
     */
    private static ?string $currentTable = null;

    /**
     * Current data from the CSV file
     * @var array $data
     */
    private static array $data = [];

    /**
     * Structure strategy for DML (load/getData/setData/save) used by StatementsHandler and TransactionsHandler.
     * @var StructureStrategy|null
     */
    private ?StructureStrategy $structureStrategy = null;

    /**
     * Constructor for the StructureHandler.
     *
     * @param IConnection $instance The instance of the connection.
     * @param StructureStrategy|null $structureStrategy Optional strategy; when provided, handler injects itself into it.
     */
    public function __construct(IConnection $instance, ?StructureStrategy $structureStrategy = null)
    {
        self::$instance = $instance;
        self::$self = $this;
        if ($structureStrategy !== null) {
            $structureStrategy->setStructureHandler($this);
            $this->structureStrategy = $structureStrategy;
        }
    }

    /**
     * Get the structure strategy (for StatementsHandler and TransactionsHandler).
     *
     * @return IStructureStrategy|null
     */
    public function getStructureStrategy(): ?IStructureStrategy
    {
        return $this->structureStrategy;
    }

    /**
     * Get the current handler instance.
     *
     * @return self|null The current handler instance.
     */
    public static function current(): ?self
    {
        return self::$self;
    }

    /**
     * Get the instance of the connection.
     *
     * @return IConnection The instance of the connection.
     */
    public function getInstance(): IConnection
    {
        return self::$instance;
    }

    /**
     * Set the value of a property.
     *
     * @param string $name The name of the property.
     * @param mixed $value The value of the property.
     * @return void
     */
    public function set(string $name, mixed $value): void
    {
        Run::call([$this->getInstance(), 'set' . ucfirst($name)], $value);
    }

    /**
     * Get the value of a property.
     *
     * @param string $name The name of the property.
     * @return mixed The value of the property.
     */
    public function get(string $name): mixed
    {
        return Run::call([$this->getInstance(), 'get' . ucfirst($name)]);
    }

    /**
     * Get the available tables (CSV files in the database directory).
     *
     * @return array
     */
    public function getTables(): array
    {
        return self::$tables;
    }

    /**
     * Get the full file path for a table, resolving TableName from Schema.ini when available.
     *
     * @param string $table The table name or filename.
     * @return string The full file path (or empty string for memory database).
     */
    public function getTablePath(string $table): string
    {
        if (self::$database === 'memory') {
            return '';
        }

        $database = (string) $this->get('database');
        if ($database === '') {
            return '';
        }

        $table = trim($table);
        if ($table === '') {
            return '';
        }

        $resolved = $table;
        if (Schema::exists($database)) {
            $schemaPath = Schema::getPath($database);
            $schema = Schema::load($schemaPath);
            $section = Schema::resolveFileName($schema, $table);
            if ($section !== null) {
                $resolved = $section;
            }
        }

        $filename = $resolved;
        if (pathinfo($filename, PATHINFO_EXTENSION) === '') {
            $filename .= '.csv';
        }

        $path = rtrim($database, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

        if ($resolved !== $table && !file_exists($path)) {
            $fallback = $table;
            if (pathinfo($fallback, PATHINFO_EXTENSION) === '') {
                $fallback .= '.csv';
            }
            return rtrim($database, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fallback;
        }

        return $path;
    }

    /**
     * Set the tables list.
     *
     * @param array $tables The tables list.
     * @return void
     */
    public function setTables(array $tables): void
    {
        self::$tables = $tables;
    }

    /**
     * Get the schema.
     *
     * @return Structure|null
     */
    public function getSchema(): ?Structure
    {
        return self::$schema;
    }

    /**
     * Set the schema.
     *
     * @param Structure $structure The schema.
     * @return void
     */
    public function setSchema(Structure $structure): void
    {
        self::$schema = $structure;
    }

    /**
     * Get the structure.
     *
     * @return Structure|array|null $structure The structure.
     */
    public function getStructure(): ?Structure
    {
        return self::$schema;
    }

    /**
     * Set the structure.
     *
     * @param Structure $structure The structure.
     * @return void
     */
    public function setStructure(array|Structure|Exceptions $structure): void
    {
        self::$schema = $structure;
    }

    /**
     * Get the file.
     *
     * @return string|null $file The file.
     */
    public function getFile(): ?string
    {
        return self::$schema?->getFile();
    }

    /**
     * Get the current active table.
     *
     * @return string|null
     */
    public function getCurrentTable(): ?string
    {
        return self::$currentTable;
    }

    /**
     * Set the current active table.
     *
     * @param string|null $table The table name.
     * @return void
     */
    public function setCurrentTable(?string $table): void
    {
        self::$currentTable = $table;
    }

    /**
     * Get the current data.
     *
     * @return array
     */
    public function getData(): array
    {
        return self::$data;
    }

    /**
     * Set the data.
     *
     * @param array $data The data.
     * @return void
     */
    public function setData(array $data): void
    {
        self::$data = $data;
    }

    /**
     * Load data from a CSV table file.
     *
     * @param string|null $table The table name (CSV file without extension).
     * @return array
     * @throws Exceptions
     */
    public function load(?string $table = null): array
    {
        if ($table !== null) {
            self::$currentTable = $table;
        }

        if (empty(self::$currentTable)) {
            return [];
        }

        $database = $this->get('database');

        // For in-memory database, data is stored in memory only
        if ($database === 'memory') {
            return self::$data;
        }

        // No database path set (e.g. connection not fully initialized) – avoid creating invalid files
        if ($database === '' || $database === null) {
            return [];
        }

        $filePath = $this->getTablePath(self::$currentTable);

        if ($filePath === '' || !file_exists($filePath)) {
            if ($filePath !== '') {
                file_put_contents($filePath, '');
            }
            return [];
        }

        $schema = null;
        if ($database !== '' && Schema::exists($database)) {
            $schema = Schema::getSchemaForFile($database, self::$currentTable);
        }

        $delimiter = ($schema !== null && isset($schema['format']))
            ? (Schema::getDelimiterFromFormat($schema['format']) ?? CSV::getDelimiter())
            : CSV::getDelimiter();
        $enclosure = CSV::getEnclosure();
        $escape = CSV::getEscape();
        $hasHeader = CSV::hasHeader();

        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            throw new Exceptions('Could not open CSV file for reading: ' . $filePath);
        }

        $data = [];
        $headers = null;

        try {
            while (($row = fgetcsv($handle, 0, $delimiter, $enclosure, $escape)) !== false) {
                if ($hasHeader && $headers === null) {
                    $headers = array_map(function ($h) {
                        $h = (string) $h;
                        if (str_starts_with($h, "\xEF\xBB\xBF")) {
                            $h = substr($h, 3);
                        }
                        return $h;
                    }, $row);
                    continue;
                }

                if ($headers !== null) {
                    $padded = array_slice(array_pad($row, count($headers), null), 0, count($headers));
                    $row = array_combine($headers, $padded);
                } elseif ($schema !== null && !empty($schema['columns'])) {
                    $cols = $schema['columns'];
                    ksort($cols, SORT_NUMERIC);
                    $names = [];
                    $i = 1;
                    foreach ($cols as $col) {
                        $names[] = is_array($col) && isset($col['name']) ? $col['name'] : 'col' . ($i++);
                    }
                    $padded = array_slice(array_pad($row, count($names), null), 0, count($names));
                    $row = array_combine($names, $padded);
                } else {
                    $row = array_combine(array_map(fn($i) => 'col' . ($i + 1), array_keys($row)), $row);
                }

                if ($row !== false) {
                    $data[] = $row;
                }
            }
        } finally {
            fclose($handle);
        }

        // Apply schema if available for this table (supports TableName mapping)
        if ($schema !== null) {
            $data = Schema::applySchema($data, $schema);
        }

        self::$data = $data;

        return $data;
    }

    /**
     * Save data to a CSV table file.
     *
     * @param array $data The data to save.
     * @param string|null $table The table name (optional).
     * @return bool
     */
    public function save(array $data, ?string $table = null): bool
    {
        if ($table !== null) {
            self::$currentTable = $table;
        }

        if (empty(self::$currentTable)) {
            return false;
        }

        $database = $this->get('database');

        // For in-memory database, just update the data array
        if ($database === 'memory') {
            self::$data = $data;
            return true;
        }

        $filePath = $this->getTablePath(self::$currentTable);

        $schema = null;
        if ($database !== '' && Schema::exists($database)) {
            $schema = Schema::getSchemaForFile($database, self::$currentTable);
        }

        $delimiter = ($schema !== null && isset($schema['format']))
            ? (Schema::getDelimiterFromFormat($schema['format']) ?? CSV::getDelimiter())
            : CSV::getDelimiter();
        $enclosure = CSV::getEnclosure();
        $escape = CSV::getEscape();
        $hasHeader = CSV::hasHeader();

        $handle = fopen($filePath, 'wb');
        if ($handle === false) {
            return false;
        }

        try {
            if (empty($data)) {
                return true;
            }

            $firstRow = reset($data);
            $firstRow = is_array($firstRow) ? $firstRow : (array) $firstRow;
            $headers = array_keys($firstRow);

            if ($hasHeader) {
                fputcsv($handle, $headers, $delimiter, $enclosure, $escape);
            }

            foreach ($data as $row) {
                $row = is_array($row) ? $row : (array) $row;
                $values = [];
                foreach ($headers as $key) {
                    $values[] = $row[$key] ?? '';
                }
                if (fputcsv($handle, $values, $delimiter, $enclosure, $escape) === false) {
                    return false;
                }
            }

            return true;
        } finally {
            fclose($handle);
        }
    }

    /**
     * Reset the data state.
     *
     * @return void
     */
    public function reset(): void
    {
        self::$data = [];
        self::$currentTable = null;
    }

    /**
     * Scan the database directory for CSV files (tables).
     *
     * @return array List of table names (without .csv extension).
     */
    private function scanTables(): array
    {
        self::$tables = [];

        if (self::$database === 'memory') {
            return self::$tables;
        }

        if (!empty($this->get('database')) && is_dir($this->get('database'))) {
            $files = glob($this->get('database') . DIRECTORY_SEPARATOR . '*.csv');

            if ($files !== false) {
                foreach ($files as $file) {
                    self::$tables[] = pathinfo($file, PATHINFO_FILENAME);
                }
            }
        }

        return self::$tables;
    }

    /**
     * Mount the structure.
     *
     * @return array|Exceptions The structure.
     */
    public function mount(): array|Structure|Exceptions
    {
        $tables = $this->scanTables();

        $structureData = [
            'tables' => $tables
        ];

        $database = $this->get('database');
        if ($database !== null && $database !== '' && Schema::exists($database)) {
            $schemaPath = Schema::getPath($database);
            $schemaData = Schema::load($schemaPath);

            $structureData['schema'] = Structure::bind([
                'file' => $schemaPath,
                'data' => $schemaData
            ]);
        }

        self::$schema = Structure::bind($structureData);
        return self::$schema;
    }
}
