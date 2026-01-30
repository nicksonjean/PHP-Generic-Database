<?php

namespace GenericDatabase\Engine\JSON\Connection\Structure;

use AllowDynamicProperties;
use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Shared\Run;
use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Helpers\Parsers\Schema;
use GenericDatabase\Interfaces\Connection\IStructure;
use GenericDatabase\Generic\Connection\Structure;
use GenericDatabase\Engine\JSON\Connection\JSON;

#[AllowDynamicProperties]
class StructureHandler implements IStructure
{
    protected static IConnection $instance;

    /**
     * @var self|null Current handler instance.
     */
    private static ?self $self = null;

    /**
     * Database directory path (folder containing JSON files) or 'memory' for in-memory database
     * @var string $database
     */
    private static string $database = '';

    /**
     * Available tables (JSON files in the database folder)
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
     * Current data from the JSON file
     * @var array $data
     */
    private static array $data = [];

    /**
     * Constructor for the StructureHandler.
     *
     * @param IConnection $instance The instance of the connection.
     */
    public function __construct(IConnection $instance)
    {
        self::$instance = $instance;
        self::$self = $this;
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
     * Get the available tables (JSON files in the database directory).
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
            $filename .= '.json';
        }

        $path = rtrim($database, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

        if ($resolved !== $table && !file_exists($path)) {
            $fallback = $table;
            if (pathinfo($fallback, PATHINFO_EXTENSION) === '') {
                $fallback .= '.json';
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
     * Load data from a JSON table file.
     *
     * @param string|null $table The table name (JSON file without extension).
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
                file_put_contents($filePath, '[]');
            }
            return [];
        }

        $content = file_get_contents($filePath);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exceptions('Invalid JSON file: ' . json_last_error_msg());
        }

        if (!is_array($data)) {
            throw new Exceptions('JSON file must contain an array of objects');
        }

        // Apply schema if available for this table (supports TableName mapping)
        if ($database !== '' && Schema::exists($database)) {
            $schema = Schema::getSchemaForFile($database, self::$currentTable);
            if ($schema !== null) {
                $data = Schema::applySchema($data, $schema);
            }
        }

        self::$data = $data;

        return $data;
    }

    /**
     * Save data to a JSON table file.
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
        $flags = JSON::getDefaultEncodingFlags();
        $content = json_encode($data, $flags);

        if ($content === false) {
            return false;
        }

        $result = file_put_contents($filePath, $content);

        return $result !== false;
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
     * Scan the database directory for JSON files (tables).
     *
     * @return array List of table names (without .json extension).
     */
    private function scanTables(): array
    {
        self::$tables = [];

        if (self::$database === 'memory') {
            return self::$tables;
        }

        if (!empty($this->get('database')) && is_dir($this->get('database'))) {
            $files = glob($this->get('database') . DIRECTORY_SEPARATOR . '*.json');

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
