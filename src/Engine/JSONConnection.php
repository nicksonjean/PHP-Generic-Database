<?php

declare(strict_types=1);

namespace GenericDatabase\Engine;

use Exception;
use ReflectionException;
use AllowDynamicProperties;
use GenericDatabase\Helpers\Errors;
use GenericDatabase\Helpers\Compare;
use GenericDatabase\Shared\Singleton;
use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Helpers\Reflections;
use GenericDatabase\Helpers\Parsers\Schema;
use Dotenv\Exception\ValidationException;
use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Helpers\Zod\SchemaParser;
use GenericDatabase\Helpers\Zod\Zod\ZodError;
use GenericDatabase\Generic\Connection\Methods;
use GenericDatabase\Interfaces\Connection\IDSN;
use GenericDatabase\Helpers\Zod\SchemaValidator;
use GenericDatabase\Interfaces\Connection\IFetch;
use GenericDatabase\Interfaces\Connection\IOptions;
use GenericDatabase\Engine\JSON\Connection\JSON;
use GenericDatabase\Interfaces\Connection\IArguments;
use GenericDatabase\Interfaces\Connection\IAttributes;
use GenericDatabase\Interfaces\Connection\IStatements;
use GenericDatabase\Interfaces\Connection\ITransactions;
use GenericDatabase\Interfaces\Connection\IStructure;
use GenericDatabase\Engine\FlatFile\DataProcessor;
use GenericDatabase\Engine\JSON\Connection\DSN\DSNHandler;
use GenericDatabase\Engine\JSON\Connection\Fetch\FetchHandler;
use GenericDatabase\Engine\JSON\Connection\Report\ReportHandler;
use GenericDatabase\Engine\JSON\Connection\Options\OptionsHandler;
use GenericDatabase\Engine\JSON\Connection\Arguments\ArgumentsHandler;
use GenericDatabase\Engine\JSON\Connection\Attributes\AttributesHandler;
use GenericDatabase\Engine\JSON\Connection\Fetch\Strategy\FetchStrategy;
use GenericDatabase\Engine\JSON\Connection\Statements\StatementsHandler;
use GenericDatabase\Engine\JSON\Connection\Transactions\TransactionsHandler;
use GenericDatabase\Engine\JSON\Connection\Arguments\Strategy\ArgumentsStrategy;
use GenericDatabase\Engine\JSON\Connection\Structure\StructureHandler;
use GenericDatabase\Generic\Connection\Structure;

/**
 * JSON Connection class for flat file database operations.
 * Provides SQL-like operations on JSON files with Schema.ini support.
 *
 * @method static JSONConnection|void setDriver(mixed $value) Sets a driver from the database.
 * @method static JSONConnection|string getDriver($value = null) Retrieves a driver from the database.
 * @method static JSONConnection|void setHost(mixed $value) Sets a host from the database.
 * @method static JSONConnection|string getHost($value = null) Retrieves a host from the database.
 * @method static JSONConnection|void setPort(mixed $value) Sets a port from the database.
 * @method static JSONConnection|int getPort($value = null) Retrieves a port from the database.
 * @method static JSONConnection|void setUser(mixed $value) Sets a user from the database.
 * @method static JSONConnection|string getUser($value = null) Retrieves a user from the database.
 * @method static JSONConnection|void setPassword(mixed $value) Sets a password from the database.
 * @method static JSONConnection|string getPassword($value = null) Retrieves a password from the database.
 * @method static JSONConnection|void setDatabase(mixed $value) Sets a database name from the database.
 * @method static JSONConnection|string getDatabase($value = null) Retrieves a database name from the database.
 * @method static JSONConnection|void setOptions(mixed $value) Sets an options from the database.
 * @method static JSONConnection|array|null getOptions($value = null) Retrieves an options from the database.
 * @method static JSONConnection|static setConnected(mixed $value) Sets a connected status from the database.
 * @method static JSONConnection|mixed getConnected($value = null) Retrieves a connected status from the database.
 * @method static JSONConnection|void setDsn(mixed $value) Sets a dsn string from the database.
 * @method static JSONConnection|mixed getDsn($value = null) Retrieves a dsn string from the database.
 * @method static JSONConnection|void setAttributes(mixed $value) Sets an attributes from the database.
 * @method static JSONConnection|mixed getAttributes($value = null) Retrieves an attributes from the database.
 * @method static JSONConnection|void setCharset(mixed $value) Sets a charset from the database.
 * @method static JSONConnection|string getCharset($value = null) Retrieves a charset from the database.
 * @method static JSONConnection|void setException(mixed $value) Sets an exception from the database.
 * @method static JSONConnection|mixed getException($value = null) Retrieves an exception from the database.
 */
#[AllowDynamicProperties]
class JSONConnection implements IConnection, IFetch, IStatements, IDSN, IArguments, ITransactions
{
    use Methods;
    use Singleton;

    /**
     * Instance of the connection with database
     * @var mixed $connection
     */
    private static mixed $connection;

    private static IFetch $fetchHandler;

    private static IStatements $statementsHandler;

    private static IDSN $dsnHandler;

    private static IAttributes $attributesHandler;

    private static IOptions $optionsHandler;

    private static IArguments $argumentsHandler;

    private static ITransactions $transactionsHandler;

    private static IStructure $structureHandler;

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
     * Schema definition (loaded from Schema.ini if exists)
     * @var array|null $schema
     */
    private static ?array $schema = null;

    /**
     * Transaction state
     * @var bool $inTransaction
     */
    private static bool $inTransaction = false;

    /**
     * Backup data for transaction rollback
     * @var array|null $transactionBackup
     */
    private static ?array $transactionBackup = null;

    /**
     * Empty constructor since initialization is handled by traits and interface methods
     */
    public function __construct()
    {
        self::$fetchHandler = new FetchHandler($this, new FetchStrategy());
        self::$optionsHandler = new OptionsHandler($this);
        self::$dsnHandler = new DSNHandler($this);
        self::$statementsHandler = new StatementsHandler($this, self::$optionsHandler, new ReportHandler());
        self::$attributesHandler = new AttributesHandler($this, self::$optionsHandler);
        self::$argumentsHandler = new ArgumentsHandler($this, self::$optionsHandler, new ArgumentsStrategy());
        self::$transactionsHandler = new TransactionsHandler($this);
        self::$structureHandler = new StructureHandler($this);
    }

    private function getFetchHandler(): IFetch
    {
        return self::$fetchHandler;
    }

    private function getStatementsHandler(): IStatements
    {
        return self::$statementsHandler;
    }

    private function getDsnHandler(): IDSN
    {
        return self::$dsnHandler;
    }

    private function getAttributesHandler(): IAttributes
    {
        return self::$attributesHandler;
    }

    private function getOptionsHandler(): IOptions
    {
        return self::$optionsHandler;
    }

    private function getArgumentsHandler(): IArguments
    {
        return self::$argumentsHandler;
    }

    private function getTransactionsHandler(): ITransactions
    {
        return self::$transactionsHandler;
    }

    private function getStructureHandler(): IStructure
    {
        return self::$structureHandler;
    }

    /**
     * Triggered when invoking inaccessible methods in an object context
     *
     * @param string $name Name of the method
     * @param array $arguments Array of arguments
     * @return IConnection|string|int|bool|array|null
     * @throws ReflectionException
     */
    public function __call(string $name, array $arguments): IConnection|string|int|bool|array|null
    {
        return $this->getArgumentsHandler()->__call($name, $arguments);
    }

    /**
     * Triggered when invoking inaccessible methods in a static context
     *
     * @param string $name Name of the static method
     * @param array $arguments Array of arguments
     * @return IConnection|string|int|bool|array|null
     * @throws ReflectionException
     */
    public static function __callStatic(string $name, array $arguments): IConnection|string|int|bool|array|null
    {
        return self::getInstance()->getArgumentsHandler()->__callStatic($name, $arguments);
    }

    /**
     * This method is responsible for prepare the connection options before connect.
     *
     * @return JSONConnection
     */
    private function preConnect(): JSONConnection
    {
        $this->getOptionsHandler()->setOptions(static::getOptions());
        static::setOptions($this->getOptionsHandler()->getOptions());
        static::setStructure($this->getStructureHandler()->mount());
        static::setDsn($this->parseDsn());
        return $this;
    }

    /**
     * This method is responsible for update in date late binding the connection.
     *
     * @return JSONConnection
     */
    private function postConnect(): JSONConnection
    {
        $this->getOptionsHandler()->define();
        $this->getAttributesHandler()->define();
        return $this;
    }

    /**
     * Get the full file path for a table.
     *
     * @param string $table The table name.
     * @return string The full file path (or empty string for memory database).
     */
    private function getTablePath(string $table): string
    {
        return $this->getStructureHandler()->getTablePath($table);
    }

    /**
     * Get the schema.
     *
     * @return Structure|array|null $schema The schema.
     * @return void
     */
    public function getSchema(): ?Structure
    {
        return $this->getStructureHandler()->getSchema()?->getSchema();
    }

    /**
     * Get the schema file.
     *
     * @return string|null $file The file.
     * @return void
     */
    public function getSchemaFile(): ?string
    {
        return $this->getStructureHandler()->getSchema()?->getFile();
    }

    /**
     * Get the schema data.
     *
     * @return array|null $data The data.
     * @return void
     */
    public function getSchemaData(): ?array
    {
        return $this->getStructureHandler()->getSchema()?->getData();
    }

    /**
     * Get the tables.
     *
     * @return array|null $tables The tables.
     * @return void
     */
    public function getTables(): ?array
    {
        return $this->getStructureHandler()->getTables();
    }

    /**
     * Set the tables.
     *
     * @param array $tables The tables.
     * @return void
     */
    public function setTables(array $tables): void
    {
        $this->getStructureHandler()->setTables($tables);
    }

    /**
     * Get the structure.
     *
     * @return Structure|array|null $structure The structure.
     * @return void
     */
    public function getStructure(): ?Structure
    {
        return $this->getStructureHandler()->getStructure();
    }

    /**
     * Set the structure.
     *
     * @param Structure $structure The structure.
     * @return void
     */
    public function setStructure(Structure $structure): void
    {
        $this->getStructureHandler()->setStructure($structure);
    }

    /**
     * This method is responsible for creating a new instance of the JSON connection.
     *
     * @param string $database The path of the database directory or 'memory' for in-memory database
     * @return JSONConnection
     * @throws Exception
     */
    private function realConnect(string $database): JSONConnection
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
                $isMemory = $database === 'memory';

                if (!$isMemory) {
                    // Resolve relative paths to absolute paths
                    $resolvedPath = $database;
                    if (!is_dir($database)) {
                        // Try to resolve relative to the project root (where composer.json typically is)
                        $projectRoot = defined('PATH_ROOT') ? constant('PATH_ROOT') : getcwd();
                        $potentialPath = realpath($projectRoot . DIRECTORY_SEPARATOR . $database);

                        if ($potentialPath !== false && is_dir($potentialPath)) {
                            $resolvedPath = $potentialPath;
                            static::setDatabase($resolvedPath);
                            self::$database = $resolvedPath;
                        } else {
                            throw new Exceptions("Database path " . $database . " directory does not exists: ");
                        }
                    }
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

    /**
     * Connect to the JSON database (folder).
     *
     * @return JSONConnection
     * @throws Exceptions
     */
    public function connect(): JSONConnection
    {
        try {
            $this->setInstance($this);
            $this
                ->preConnect()
                ->getInstance()
                ->realConnect(
                    static::getDatabase()
                )
                ->postConnect()
                ->setConnected(true);
            return $this;
        } catch (Exception $error) {
            $this->disconnect();
            throw new Exceptions($error->getMessage());
        }
    }

    /**
     * Ping the connection.
     *
     * @return bool
     */
    public function ping(): bool
    {
        $database = static::getDatabase();
        if ($database === 'memory') {
            return $this->getInstance()->getConnected();
        }
        return is_dir($database);
    }

    /**
     * Disconnect from the JSON database.
     *
     * @return void
     */
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

    /**
     * Check if connected.
     *
     * @return bool
     */
    public function isConnected(): bool
    {
        if (self::$database === 'memory') {
            return $this->getInstance()->getConnected();
        }
        return is_dir(self::$database) && $this->getInstance()->getConnected();
    }

    /**
     * This method is responsible for parsing the DSN from DSN class.
     *
     * @return string|Exceptions
     */
    private function parseDsn(): string|Exceptions
    {
        return $this->getDsnHandler()->parse();
    }

    /**
     * Get the connection instance (data array).
     *
     * @return mixed
     */
    public function getConnection(): mixed
    {
        return self::$connection;
    }

    /**
     * Set the connection instance.
     *
     * @param mixed $connection The connection.
     * @return mixed
     */
    public function setConnection(mixed $connection): mixed
    {
        self::$connection = $connection;
        return self::$connection;
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

        // For in-memory database, data is stored in memory only
        if (self::$database === 'memory') {
            // In-memory: data is already in self::$data
            // Add to tables list if not present
            if (!in_array(self::$currentTable, self::$tables)) {
                self::$tables[] = self::$currentTable;
                $this->tables = self::$tables;
            }
            return self::$data;
        }

        $filePath = $this->getTablePath(self::$currentTable);

        if (!file_exists($filePath)) {
            // Create empty file
            file_put_contents($filePath, '[]');
            // Add to tables list if not present
            if (!in_array(self::$currentTable, self::$tables)) {
                self::$tables[] = self::$currentTable;
                $this->tables = self::$tables;
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
        $database = (string) $this->get('database');
        if ($database !== '' && Schema::exists($database)) {
            $schema = Schema::getSchemaForFile($database, self::$currentTable);
            if ($schema !== null) {
                $data = Schema::applySchema($data, $schema);
            }
        }

        self::$data = $data;
        self::$connection = $data;

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

        // For in-memory database, just update the data array
        if (self::$database === 'memory') {
            self::$data = $data;
            self::$connection = $data;
            // Add to tables list if not present
            if (!in_array(self::$currentTable, self::$tables)) {
                self::$tables[] = self::$currentTable;
                $this->tables = self::$tables;
            }
            return true;
        }

        $filePath = $this->getTablePath(self::$currentTable);
        $flags = JSON::getDefaultEncodingFlags();
        $content = json_encode($data, $flags);

        if ($content === false) {
            return false;
        }

        $result = file_put_contents($filePath, $content);

        // Add to tables list if not present
        if ($result !== false && !in_array(self::$currentTable, self::$tables)) {
            self::$tables[] = self::$currentTable;
            $this->tables = self::$tables;
        }

        return $result !== false;
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
        self::$connection = $data;
    }

    /**
     * Set the current active table.
     *
     * @param string $table The table name.
     * @return JSONConnection
     */
    public function from(string $table): JSONConnection
    {
        self::$currentTable = str_replace('.json', '', $table);
        $this->load(self::$currentTable);
        return $this;
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
     * Begin a transaction.
     *
     * @return bool
     */
    public function beginTransaction(): bool
    {
        if (self::$inTransaction) {
            return false;
        }

        self::$transactionBackup = self::$data;
        self::$inTransaction = true;
        return true;
    }

    /**
     * Commit the transaction.
     *
     * @return bool
     */
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

    /**
     * Rollback the transaction.
     *
     * @return bool
     */
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

    /**
     * Check if in transaction.
     *
     * @return bool
     */
    public function inTransaction(): bool
    {
        return self::$inTransaction;
    }

    /**
     * Get the last insert ID.
     *
     * @param string|null $name The name.
     * @return string|int|false
     */
    public function lastInsertId(?string $name = null): string|int|false
    {
        return $this->getStatementsHandler()->lastInsertId($name);
    }

    /**
     * Quote a value.
     *
     * @param mixed ...$params The value to quote.
     * @return string|int
     */
    public function quote(mixed ...$params): string|int
    {
        return $this->getStatementsHandler()->quote(...$params);
    }

    /**
     * Insert a new row.
     *
     * @param array $row The row to insert.
     * @return bool
     */
    public function insert(array $row): bool
    {
        $processor = new DataProcessor(self::$data, self::$schema);
        $result = $processor->insert($row);

        if ($result) {
            self::$data = $processor->getData();
            self::$connection = self::$data;

            // Auto-save if not in transaction and auto-save is enabled
            if (!self::$inTransaction && JSON::getAttribute(JSON::ATTR_AUTO_SAVE)) {
                $this->save(self::$data);
            }

            // Update last insert ID using reflection
            $statementsHandler = $this->getStatementsHandler();
            if (method_exists($statementsHandler, 'setLastInsertId')) {
                $lastId = isset($row['id']) ? (int) $row['id'] : count(self::$data);
                $statementsHandler->setLastInsertId($lastId);
            }
        }

        return $result;
    }

    /**
     * Update rows matching the criteria.
     *
     * @param array $data The data to update.
     * @param array $where The criteria.
     * @return int
     */
    public function update(array $data, array $where): int
    {
        $processor = new DataProcessor(self::$data, self::$schema);
        $affected = $processor->update($data, $where);

        if ($affected > 0) {
            self::$data = $processor->getData();
            self::$connection = self::$data;

            if (!self::$inTransaction && JSON::getAttribute(JSON::ATTR_AUTO_SAVE)) {
                $this->save(self::$data);
            }
        }

        $this->getStatementsHandler()->setAffectedRows($affected);
        return $affected;
    }

    /**
     * Delete rows matching the criteria.
     *
     * @param array $where The criteria.
     * @return int
     */
    public function delete(array $where): int
    {
        $processor = new DataProcessor(self::$data, self::$schema);
        $deleted = $processor->delete($where);

        if ($deleted > 0) {
            self::$data = $processor->getData();
            self::$connection = self::$data;

            if (!self::$inTransaction && JSON::getAttribute(JSON::ATTR_AUTO_SAVE)) {
                $this->save(self::$data);
            }
        }

        $this->getStatementsHandler()->setAffectedRows($deleted);
        return $deleted;
    }

    /**
     * Select rows matching the criteria.
     *
     * @param array $columns The columns to select.
     * @param array $where The criteria.
     * @return array
     */
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

    /**
     * Reset all metadata.
     *
     * @return void
     */
    public function setAllMetadata(): void
    {
        $this->getStatementsHandler()->setAllMetadata();
    }

    /**
     * Get all metadata.
     *
     * @return object
     */
    public function getAllMetadata(): object
    {
        // Force query execution if there's a pending query but no results yet
        $queryString = $this->getQueryString();
        if (!empty($queryString)) {
            $currentRows = $this->getStatementsHandler()->getQueryRows();
            // If metadata is empty, trigger query execution by calling fetch method
            if ($currentRows === 0 || $currentRows === false) {
                // Use reflection to call the executeStoredQuery method to populate metadata
                try {
                    $reflection = new \ReflectionClass($this);
                    $fetchHandlerMethod = $reflection->getMethod('getFetchHandler');
                    $fetchHandlerMethod->setAccessible(true);
                    $fetchHandler = $fetchHandlerMethod->invoke($this);

                    if ($fetchHandler) {
                        // Call fetch once to trigger query execution without consuming all results
                        $reflection = new \ReflectionClass($fetchHandler);
                        if ($reflection->hasMethod('fetch')) {
                            $fetchMethod = $reflection->getMethod('fetch');
                            $fetchMethod->invoke($fetchHandler);
                            // Reset cursor to allow fetchAll to work normally later
                            if ($reflection->hasMethod('reset')) {
                                $resetMethod = $reflection->getMethod('reset');
                                $resetMethod->invoke($fetchHandler);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    // Silently fail if reflection doesn't work
                }
            }
        }

        return $this->getStatementsHandler()->getAllMetadata();
    }

    /**
     * Get the query string.
     *
     * @return string
     */
    public function getQueryString(): string
    {
        return $this->getStatementsHandler()->getQueryString();
    }

    /**
     * Set the query string.
     *
     * @param string $params The query string.
     * @return void
     */
    public function setQueryString(string $params): void
    {
        $this->getStatementsHandler()->setQueryString($params);
    }

    /**
     * Get the query parameters.
     *
     * @return array|null
     */
    public function getQueryParameters(): ?array
    {
        return $this->getStatementsHandler()->getQueryParameters();
    }

    /**
     * Set the query parameters.
     *
     * @param array|null $params The parameters.
     * @return void
     */
    public function setQueryParameters(?array $params): void
    {
        $this->getStatementsHandler()->setQueryParameters($params);
    }

    /**
     * Get the number of query rows.
     *
     * @return int|false
     */
    public function getQueryRows(): int|false
    {
        return $this->getStatementsHandler()->getQueryRows();
    }

    /**
     * Set the number of query rows.
     *
     * @param callable|int|false $params The number of rows.
     * @return void
     */
    public function setQueryRows(callable|int|false $params): void
    {
        $this->getStatementsHandler()->setQueryRows($params);
    }

    /**
     * Get the number of query columns.
     *
     * @return int|false
     */
    public function getQueryColumns(): int|false
    {
        return $this->getStatementsHandler()->getQueryColumns();
    }

    /**
     * Set the number of query columns.
     *
     * @param int|false $params The number of columns.
     * @return void
     */
    public function setQueryColumns(int|false $params): void
    {
        $this->getStatementsHandler()->setQueryColumns($params);
    }

    /**
     * Get the number of affected rows.
     *
     * @return int|false
     */
    public function getAffectedRows(): int|false
    {
        return $this->getStatementsHandler()->getAffectedRows();
    }

    /**
     * Set the number of affected rows.
     *
     * @param int|false $params The number of affected rows.
     * @return void
     */
    public function setAffectedRows(int|false $params): void
    {
        $this->getStatementsHandler()->setAffectedRows($params);
    }

    /**
     * Get the statement.
     *
     * @return mixed
     */
    public function getStatement(): mixed
    {
        return $this->getStatementsHandler()->getStatement();
    }

    /**
     * Set the statement.
     *
     * @param mixed $statement The statement.
     * @return void
     */
    public function setStatement(mixed $statement): void
    {
        $this->getStatementsHandler()->setStatement($statement);
    }

    /**
     * Bind a parameter.
     *
     * @param object $params The parameter object.
     * @return void
     */
    public function bindParam(object $params): void
    {
        $this->getStatementsHandler()->bindParam($params);
    }

    /**
     * Parse a query.
     *
     * @param mixed ...$params The parameters.
     * @return string
     */
    public function parse(mixed ...$params): string
    {
        return $this->getStatementsHandler()->parse(...$params);
    }

    /**
     * Execute a query.
     *
     * @param mixed ...$params The query parameters.
     * @return static|null
     */
    public function query(mixed ...$params): static|null
    {
        $this->getStatementsHandler()->query(...$params);
        return $this;
    }

    /**
     * Prepare a statement.
     *
     * @param mixed ...$params The parameters.
     * @return static|null
     */
    public function prepare(mixed ...$params): static|null
    {
        $this->getStatementsHandler()->prepare(...$params);
        return $this;
    }

    /**
     * Execute a statement.
     *
     * @param mixed ...$params The parameters.
     * @return mixed
     */
    public function exec(mixed ...$params): mixed
    {
        return $this->getStatementsHandler()->exec(...$params);
    }

    // IFetch implementation

    /**
     * Fetch the next row.
     *
     * @param int|null $fetchStyle The fetch style.
     * @param mixed|null $fetchArgument The fetch argument.
     * @param mixed|null $optArgs Additional options.
     * @return mixed
     */
    public function fetch(?int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): mixed
    {
        return $this->getFetchHandler()->fetch($fetchStyle, $fetchArgument, $optArgs);
    }

    /**
     * Fetch all rows.
     *
     * @param int|null $fetchStyle The fetch style.
     * @param mixed|null $fetchArgument The fetch argument.
     * @param mixed|null $optArgs Additional options.
     * @return array|bool
     */
    public function fetchAll(?int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): array|bool
    {
        return $this->getFetchHandler()->fetchAll($fetchStyle, $fetchArgument, $optArgs);
    }

    /**
     * Get an attribute.
     *
     * @param mixed $name The attribute name.
     * @return mixed
     * @throws ReflectionException
     */
    public function getAttribute(mixed $name): mixed
    {
        return JSON::getAttribute($name);
    }

    /**
     * Set an attribute.
     *
     * @param mixed $name The attribute name.
     * @param mixed $value The attribute value.
     * @return void
     * @throws ReflectionException
     */
    public function setAttribute(mixed $name, mixed $value): void
    {
        JSON::setAttribute($name, $value);
    }

    /**
     * Get the error code.
     *
     * @param mixed $inst The instance.
     * @return int|string|bool
     */
    public function errorCode(mixed $inst = null): int|string|bool
    {
        return 0;
    }

    /**
     * Get the error info.
     *
     * @param mixed $inst The instance.
     * @return string|bool|array
     */
    public function errorInfo(mixed $inst = null): string|bool|array
    {
        return '';
    }
}

