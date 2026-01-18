<?php

declare(strict_types=1);

namespace GenericDatabase\Engine;

use Exception;
use AllowDynamicProperties;
use GenericDatabase\Shared\Singleton;
use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Helpers\Parsers\Schema;
use GenericDatabase\Helpers\Zod\SchemaParser;
use GenericDatabase\Helpers\Zod\Zod\ZodError;
use GenericDatabase\Helpers\Zod\SchemaValidator;
use Dotenv\Exception\ValidationException;
use GenericDatabase\Generic\Connection\Methods;
use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Interfaces\IFlatFileConnection;
use GenericDatabase\Interfaces\Connection\IFetch;
use GenericDatabase\Interfaces\Connection\IStatements;
use GenericDatabase\Engine\YAML\Connection\YAML;
use GenericDatabase\Engine\FlatFile\DataProcessor;
use GenericDatabase\Helpers\Parsers\YAML as YAMLParser;

/**
 * YAML Connection class for flat file database operations.
 * Provides SQL-like operations on YAML files with Schema.ini support.
 *
 * @method static YAMLConnection|void setDatabase(mixed $value) Sets the database (directory) path.
 * @method static YAMLConnection|string getDatabase($value = null) Retrieves the database path.
 * @method static YAMLConnection|void setTables(mixed $value) Sets the tables array.
 * @method static YAMLConnection|array getTables($value = null) Retrieves the tables array.
 */
#[AllowDynamicProperties]
class YAMLConnection implements IConnection, IFlatFileConnection, IFetch, IStatements
{
    use Methods;
    use Singleton;

    private static mixed $connection = null;
    private static string $database = '';
    private static array $tables = [];
    private static ?string $currentTable = null;
    private static array $data = [];
    private static ?array $schema = null;
    private static bool $inTransaction = false;
    private static ?array $transactionBackup = null;
    private static bool $connected = false;
    private static string $engine = 'yaml';
    private static int $inlineLevel = 2;
    private static int $indentation = 2;

    // Statement properties
    private string $queryString = '';
    private ?array $queryParameters = null;
    private int|false $queryRows = 0;
    private int|false $queryColumns = 0;
    private int|false $affectedRows = 0;
    private mixed $statement = null;
    private int $lastInsertId = 0;
    private int $cursor = 0;

    public function __construct()
    {
    }

    public static function getEngine(): string
    {
        return self::$engine;
    }

    public function __call(string $name, array $arguments): IConnection|string|int|bool|array|null
    {
        $method = substr($name, 0, 3);
        $field = lcfirst(substr($name, 3));

        if ($method === 'set') {
            // Store in Settings via __set magic method from Methods trait
            $this->$field = $arguments[0] ?? null;

            // Also update internal static state for specific fields
            match ($field) {
                'database' => self::$database = rtrim($arguments[0] ?? '', DIRECTORY_SEPARATOR),
                'tables' => self::$tables = (array) ($arguments[0] ?? []),
                'connected' => self::$connected = (bool) ($arguments[0] ?? false),
                'schema' => self::$schema = is_array($arguments[0] ?? null) ? $arguments[0] : null,
                'inlineLevel' => self::$inlineLevel = (int) ($arguments[0] ?? 2),
                'indentation' => self::$indentation = (int) ($arguments[0] ?? 2),
                default => null
            };
            return $this;
        } elseif ($method === 'get') {
            // For internal state fields, return from static properties
            return match ($field) {
                'tables' => self::$tables,
                'connected' => self::$connected,
                'engine' => self::$engine,
                'schema' => self::$schema,
                'inlineLevel' => self::$inlineLevel,
                'indentation' => self::$indentation,
                // For all other fields, get from Settings via __get magic method
                default => $this->$field
            };
        }
        return $this;
    }

    public static function __callStatic(string $name, array $arguments): IConnection|string|int|bool|array|null
    {
        return self::getInstance()->__call($name, $arguments);
    }

    public function getTables(): array
    {
        return self::$tables;
    }
    public function setTables(array $tables): void
    {
        self::$tables = $tables;
    }

    private function scanTables(): array
    {
        $tables = [];
        if (!empty(self::$database) && is_dir(self::$database)) {
            $yamlFiles = glob(self::$database . DIRECTORY_SEPARATOR . '*.yaml');
            $ymlFiles = glob(self::$database . DIRECTORY_SEPARATOR . '*.yml');
            $files = array_merge($yamlFiles ?: [], $ymlFiles ?: []);
            foreach ($files as $file) {
                $tables[] = pathinfo($file, PATHINFO_FILENAME);
            }
        }
        return array_unique($tables);
    }

    private function getTablePath(string $table): string
    {
        $table = preg_replace('/\.(yaml|yml)$/', '', $table);
        // Check if .yaml exists, otherwise use .yml
        $yamlPath = self::$database . DIRECTORY_SEPARATOR . $table . '.yaml';
        $ymlPath = self::$database . DIRECTORY_SEPARATOR . $table . '.yml';
        return file_exists($ymlPath) && !file_exists($yamlPath) ? $ymlPath : $yamlPath;
    }

    public function getSchema(): ?array
    {
        return self::$schema;
    }
    public function setSchema(?array $schema): void
    {
        self::$schema = $schema;
    }

    /**
     * This method is responsible for creating a new instance of the YAML connection.
     *
     * @param string $database The path of the database directory
     * @return YAMLConnection
     * @throws Exception
     */
    private function realConnect(string $database): YAMLConnection
    {
        try {
            $schemaJson = __DIR__ . '/JSON/Connection/JSON.json';
            $schemaParser = new SchemaParser($schemaJson);
            $validJson = $schemaParser->parse([
                'database' => $database,
                'charset' => static::getCharset() ?? 'UTF-8'
            ]);
            $validator = new SchemaValidator($schemaJson);

            if ($validator->validate($validJson)) {
                if (!is_dir($database)) {
                    if (!mkdir($database, 0755, true) && !is_dir($database)) {
                        throw new Exceptions("Database directory does not exist and could not be created: " . $database);
                    }
                }

                self::$tables = $this->scanTables();
                $this->tables = self::$tables;

                if (Schema::exists($database)) {
                    $schemaPath = Schema::getPath($database);
                    self::$schema = Schema::load($schemaPath);
                    $this->schema = self::$schema;
                } else {
                    $this->schema = null;
                }

                self::$connection = self::$data;
            } else {
                $errors = $validator->getErrors();
                if (!empty($errors)) {
                    throw new ValidationException(implode("\n", array_map(fn($error) => "- $error", $errors)));
                }
            }
        } catch (ZodError $e) {
            $errorMessages = [];
            foreach ($e->errors as $error) {
                $errorMessages[] = "- " . implode('.', $error['path']) . ": {$error['message']}";
            }
            throw new Exceptions(implode("\n", $errorMessages));
        } catch (Exception $error) {
            throw new Exceptions($error->getMessage());
        }
        return $this;
    }

    public function connect(): YAMLConnection
    {
        try {
            $this->setInstance($this);
            $this
                ->getInstance()
                ->realConnect(static::getDatabase())
                ->setConnected(true);
            return $this;
        } catch (Exception $error) {
            $this->disconnect();
            throw new Exceptions($error->getMessage());
        }
    }

    public function ping(): bool
    {
        return is_dir(static::getDatabase());
    }

    public function disconnect(): void
    {
        if ($this->isConnected()) {
            static::setConnected(false);
            $this->setConnection(null);
            self::$data = [];
            self::$tables = [];
            self::$currentTable = null;
        }
    }

    public function isConnected(): bool
    {
        return is_dir(self::$database) && $this->getInstance()->getConnected();
    }
    public function getConnection(): mixed
    {
        return self::$connection;
    }
    public function setConnection(mixed $connection): mixed
    {
        self::$connection = $connection;
        return self::$connection;
    }

    public function load(?string $table = null): array
    {
        if ($table !== null) {
            self::$currentTable = $table;
        }

        if (empty(self::$currentTable)) {
            return [];
        }

        $filePath = $this->getTablePath(self::$currentTable);

        if (!file_exists($filePath)) {
            file_put_contents($filePath, "---\n");
            if (!in_array(self::$currentTable, self::$tables)) {
                self::$tables[] = self::$currentTable;
            }
            return [];
        }

        $content = file_get_contents($filePath);
        if (empty(trim($content)) || trim($content) === '---') {
            return [];
        }

        $data = YAMLParser::parseYaml($content);

        if (!is_array($data)) {
            $data = [];
        }

        $schemaKey = self::$currentTable . '.yaml';
        if (self::$schema !== null && isset(self::$schema[$schemaKey])) {
            $data = Schema::applySchema($data, self::$schema[$schemaKey]);
        }

        self::$data = $data;
        self::$connection = $data;

        return $data;
    }

    public function save(array $data, ?string $table = null): bool
    {
        if ($table !== null) {
            self::$currentTable = $table;
        }

        if (empty(self::$currentTable)) {
            return false;
        }

        $filePath = $this->getTablePath(self::$currentTable);
        $content = YAMLParser::emitYaml($data, self::$inlineLevel, self::$indentation);
        $result = file_put_contents($filePath, $content);

        if ($result !== false && !in_array(self::$currentTable, self::$tables)) {
            self::$tables[] = self::$currentTable;
        }

        return $result !== false;
    }

    public function getData(): array
    {
        return self::$data;
    }
    public function setData(array $data): void
    {
        self::$data = $data;
        self::$connection = $data;
    }

    public function from(string $table): YAMLConnection
    {
        self::$currentTable = preg_replace('/\.(yaml|yml)$/', '', $table);
        $this->load(self::$currentTable);
        return $this;
    }

    public function getCurrentTable(): ?string
    {
        return self::$currentTable;
    }

    public function beginTransaction(): bool
    {
        if (self::$inTransaction) {
            return false;
        }
        self::$transactionBackup = self::$data;
        self::$inTransaction = true;
        return true;
    }

    public function commit(): bool
    {
        if (!self::$inTransaction) {
            return false;
        }
        $result = $this->save(self::$data);
        self::$inTransaction = false;
        self::$transactionBackup = null;
        return $result;
    }

    public function rollback(): bool
    {
        if (!self::$inTransaction) {
            return false;
        }
        if (self::$transactionBackup !== null) {
            self::$data = self::$transactionBackup;
            self::$connection = self::$data;
        }
        self::$inTransaction = false;
        self::$transactionBackup = null;
        return true;
    }

    public function inTransaction(): bool
    {
        return self::$inTransaction;
    }
    public function lastInsertId(?string $name = null): string|int|false
    {
        return $this->lastInsertId;
    }
    public function quote(mixed ...$params): string|int
    {
        $value = $params[0] ?? '';
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_null($value)) {
            return 'NULL';
        }
        return "'" . addslashes((string) $value) . "'";
    }

    public function insert(array $row): bool
    {
        $processor = new DataProcessor(self::$data, self::$schema);
        $result = $processor->insert($row);

        if ($result) {
            self::$data = $processor->getData();
            self::$connection = self::$data;
            if (!self::$inTransaction) {
                $this->save(self::$data);
            }
            $this->lastInsertId = count(self::$data);
        }
        return $result;
    }

    public function update(array $data, array $where): int
    {
        $processor = new DataProcessor(self::$data, self::$schema);
        $affected = $processor->update($data, $where);

        if ($affected > 0) {
            self::$data = $processor->getData();
            self::$connection = self::$data;
            if (!self::$inTransaction) {
                $this->save(self::$data);
            }
        }
        $this->affectedRows = $affected;
        return $affected;
    }

    public function delete(array $where): int
    {
        $processor = new DataProcessor(self::$data, self::$schema);
        $deleted = $processor->delete($where);

        if ($deleted > 0) {
            self::$data = $processor->getData();
            self::$connection = self::$data;
            if (!self::$inTransaction) {
                $this->save(self::$data);
            }
        }
        $this->affectedRows = $deleted;
        return $deleted;
    }

    public function selectWhere(array $columns, array $where): array
    {
        $processor = new DataProcessor(self::$data, self::$schema);
        if (!empty($where)) {
            $processor->where($where);
        }
        if (!empty($columns) && !in_array('*', $columns)) {
            $processor->select($columns);
        }
        return $processor->getData();
    }

    // IStatements implementation
    public function setAllMetadata(): void
    {
        $this->queryRows = 0;
        $this->queryColumns = 0;
        $this->affectedRows = 0;
    }
    public function getAllMetadata(): object
    {
        return (object) ['queryRows' => $this->queryRows, 'queryColumns' => $this->queryColumns, 'affectedRows' => $this->affectedRows];
    }
    public function getQueryString(): string
    {
        return $this->queryString;
    }
    public function setQueryString(string $params): void
    {
        $this->queryString = $params;
    }
    public function getQueryParameters(): ?array
    {
        return $this->queryParameters;
    }
    public function setQueryParameters(?array $params): void
    {
        $this->queryParameters = $params;
    }
    public function getQueryRows(): int|false
    {
        return $this->queryRows;
    }
    public function setQueryRows(callable|int|false $params): void
    {
        $this->queryRows = is_callable($params) ? $params() : $params;
    }
    public function getQueryColumns(): int|false
    {
        return $this->queryColumns;
    }
    public function setQueryColumns(int|false $params): void
    {
        $this->queryColumns = $params;
    }
    public function getAffectedRows(): int|false
    {
        return $this->affectedRows;
    }
    public function setAffectedRows(int|false $params): void
    {
        $this->affectedRows = $params;
    }
    public function getStatement(): mixed
    {
        return $this->statement;
    }
    public function setStatement(mixed $statement): void
    {
        $this->statement = $statement;
    }
    public function bindParam(object $params): void
    {
    }
    public function parse(mixed ...$params): string
    {
        return $params[0] ?? '';
    }
    public function query(mixed ...$params): static|null
    {
        $this->queryString = $params[0] ?? '';
        $this->statement = $this->queryString;
        return $this;
    }
    public function prepare(mixed ...$params): static|null
    {
        $this->queryString = $params[0] ?? '';
        $this->statement = $this->queryString;
        return $this;
    }
    public function exec(mixed ...$params): mixed
    {
        return $this->affectedRows;
    }

    // IFetch implementation
    public function fetch(?int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): mixed
    {
        if ($this->cursor >= count(self::$data)) {
            return false;
        }
        $row = self::$data[$this->cursor++];
        return match ($fetchStyle ?? YAML::FETCH_ASSOC) {
            YAML::FETCH_NUM => array_values((array) $row),
            YAML::FETCH_OBJ => (object) $row,
            default => (array) $row,
        };
    }

    public function fetchAll(?int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): array|bool
    {
        $result = [];
        foreach (self::$data as $row) {
            $result[] = match ($fetchStyle ?? YAML::FETCH_ASSOC) {
                YAML::FETCH_NUM => array_values((array) $row),
                YAML::FETCH_OBJ => (object) $row,
                default => (array) $row,
            };
        }
        $this->cursor = count(self::$data);
        return $result;
    }

    public function getAttribute(mixed $name): mixed
    {
        return null;
    }
    public function setAttribute(mixed $name, mixed $value): void
    {
    }
    public function errorCode(mixed $inst = null): int|string|bool
    {
        return 0;
    }
    public function errorInfo(mixed $inst = null): string|bool|array
    {
        return '';
    }
}
