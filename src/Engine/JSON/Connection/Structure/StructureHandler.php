<?php

namespace GenericDatabase\Engine\JSON\Connection\Structure;

use AllowDynamicProperties;
use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Shared\Run;
use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Helpers\Parsers\Schema;
use GenericDatabase\Interfaces\Connection\IStructure;
use GenericDatabase\Generic\Connection\Structure;

#[AllowDynamicProperties]
class StructureHandler implements IStructure
{
    protected static IConnection $instance;

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
     * Constructor for the StructureHandler.
     *
     * @param IConnection $instance The instance of the connection.
     */
    public function __construct(IConnection $instance)
    {
        self::$instance = $instance;
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
     * @return void
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
    public function setStructure(Structure $structure): void
    {
        self::$schema = $structure;
    }

    /**
     * Get the file.
     *
     * @return string|null $file The file.
     * @return void
     */
    public function getFile(): ?string
    {
        return self::$schema?->getFile();
    }

    /**
     * Get the data.
     *
     * @return array|null $data The data.
     * @return void
     */
    public function getData(): ?array
    {
        return self::$schema?->getData();
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

        if (Schema::exists($this->get('database'))) {
            $schemaPath = Schema::getPath($this->get('database'));
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
