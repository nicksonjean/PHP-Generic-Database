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
use GenericDatabase\Engine\CSV\Connection\CSV;
use GenericDatabase\Engine\CSV\Connection\Fetch\FetchHandler;
use GenericDatabase\Engine\CSV\Connection\Statements\StatementsHandler;
use GenericDatabase\Engine\FlatFile\DataProcessor;
use GenericDatabase\Interfaces\Connection\IFlatFileFetch;
use GenericDatabase\Interfaces\Connection\IFlatFileStatements;
use GenericDatabase\Engine\CSV\Connection\Fetch\Strategy\FetchStrategy;

/**
 * CSV Connection class for flat file database operations.
 * Provides SQL-like operations on CSV files with Schema.ini support.
 *
 * @method static CSVConnection|void setDatabase(mixed $value) Sets the database (directory) path.
 * @method static CSVConnection|string getDatabase($value = null) Retrieves the database path.
 * @method static CSVConnection|void setTables(mixed $value) Sets the tables array.
 * @method static CSVConnection|array getTables($value = null) Retrieves the tables array.
 * @method static CSVConnection|void setDelimiter(mixed $value) Sets the CSV delimiter.
 * @method static CSVConnection|string getDelimiter($value = null) Retrieves the CSV delimiter.
 */
#[AllowDynamicProperties]
class CSVConnection implements IConnection
{
    use Methods;
    use Singleton;

    private static mixed $connection = null;
    private static string $database = '';
    private static array $tables = [];
    private static ?string $currentTable = null;
    private static array $data = [];
    private static ?array $schema = null;
    private static ?array $headers = null;
    private static bool $inTransaction = false;
    private static ?array $transactionBackup = null;
    private static bool $connected = false;
    private static IFlatFileFetch $fetchHandler;
    private static IFlatFileStatements $statementsHandler;
    private static string $engine = 'csv';

    public function __construct()
    {
        self::$fetchHandler = new FetchHandler($this, new FetchStrategy());
        self::$statementsHandler = new StatementsHandler($this);
    }

    private static function getEngine(): string
    {
        return self::$engine;
    }

    private function getFetchHandler(): IFlatFileFetch
    {
        return self::$fetchHandler;
    }

    private function getStatementsHandler(): IFlatFileStatements
    {
        return self::$statementsHandler;
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
                'delimiter' => CSV::setDelimiter($arguments[0] ?? ','),
                'enclosure' => CSV::setEnclosure($arguments[0] ?? '"'),
                'escape' => CSV::setEscape($arguments[0] ?? '\\'),
                'hasHeader' => CSV::setHasHeader((bool) ($arguments[0] ?? true)),
                'options' => CSV::setAttribute('options', $arguments[0] ?? []),
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
                'delimiter' => CSV::getDelimiter(),
                'enclosure' => CSV::getEnclosure(),
                'escape' => CSV::getEscape(),
                'hasHeader' => CSV::hasHeader(),
                'options' => CSV::getAttribute('options'),
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

    private function getTables(): array
    {
        return self::$tables;
    }

    private function setTables(array $tables): void
    {
        self::$tables = $tables;
    }

    private function scanTables(): array
    {
        $tables = [];
        if (!empty(self::$database) && is_dir(self::$database)) {
            $files = glob(self::$database . DIRECTORY_SEPARATOR . '*.csv');
            if ($files !== false) {
                foreach ($files as $file) {
                    $tables[] = pathinfo($file, PATHINFO_FILENAME);
                }
            }
        }
        return $tables;
    }

    private function getTablePath(string $table): string
    {
        $table = str_replace('.csv', '', $table);
        return self::$database . DIRECTORY_SEPARATOR . $table . '.csv';
    }

    private function getSchema(): ?array
    {
        return self::$schema;
    }

    private function setSchema(?array $schema): void
    {
        self::$schema = $schema;
    }

    /**
     * This method is responsible for creating a new instance of the CSV connection.
     *
     * @param string $database The path of the database directory
     * @return CSVConnection
     * @throws Exception
     */
    private function realConnect(string $database): CSVConnection
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

    public function connect(): CSVConnection
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
            self::$headers = null;
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

    private function load(?string $table = null): array
    {
        if ($table !== null) {
            self::$currentTable = $table;
        }

        if (empty(self::$currentTable)) {
            return [];
        }

        $filePath = $this->getTablePath(self::$currentTable);

        if (!file_exists($filePath)) {
            file_put_contents($filePath, '');
            if (!in_array(self::$currentTable, self::$tables)) {
                self::$tables[] = self::$currentTable;
            }
            return [];
        }

        $data = [];
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new Exceptions('Unable to open CSV file');
        }

        $delimiter = CSV::getDelimiter();
        $enclosure = CSV::getEnclosure();
        $escape = CSV::getEscape();
        $hasHeader = CSV::hasHeader();

        $lineNumber = 0;
        while (($row = fgetcsv($handle, 0, $delimiter, $enclosure, $escape)) !== false) {
            if ($lineNumber === 0 && $hasHeader) {
                self::$headers = $row;
            } else {
                if (self::$headers !== null) {
                    $data[] = array_combine(self::$headers, array_pad($row, count(self::$headers), null));
                } else {
                    $data[] = $row;
                }
            }
            $lineNumber++;
        }

        fclose($handle);

        if (self::$schema !== null && isset(self::$schema[self::$currentTable . '.csv'])) {
            $data = Schema::applySchema($data, self::$schema[self::$currentTable . '.csv']);
        }

        self::$data = $data;
        self::$connection = $data;

        // Clear fetch cache so new data is used on next fetch
        self::$fetchHandler->clearCache();

        return $data;
    }

    private function save(array $data, ?string $table = null): bool
    {
        if ($table !== null) {
            self::$currentTable = $table;
        }

        if (empty(self::$currentTable)) {
            return false;
        }

        $filePath = $this->getTablePath(self::$currentTable);
        $handle = fopen($filePath, 'w');

        if ($handle === false) {
            return false;
        }

        $delimiter = CSV::getDelimiter();
        $enclosure = CSV::getEnclosure();
        $escape = CSV::getEscape();

        if (self::$headers !== null && CSV::hasHeader()) {
            fputcsv($handle, self::$headers, $delimiter, $enclosure, $escape);
        } elseif (!empty($data) && is_array(reset($data))) {
            $firstRow = reset($data);
            if (array_keys($firstRow) !== range(0, count($firstRow) - 1)) {
                self::$headers = array_keys($firstRow);
                fputcsv($handle, self::$headers, $delimiter, $enclosure, $escape);
            }
        }

        foreach ($data as $row) {
            if (is_array($row)) {
                fputcsv($handle, array_values($row), $delimiter, $enclosure, $escape);
            }
        }

        fclose($handle);

        if (!in_array(self::$currentTable, self::$tables)) {
            self::$tables[] = self::$currentTable;
        }

        return true;
    }

    private function getData(): array
    {
        return self::$data;
    }

    private function setData(array $data): void
    {
        self::$data = $data;
        self::$connection = $data;
    }

    private function from(string $table): CSVConnection
    {
        self::$currentTable = str_replace('.csv', '', $table);
        $this->load(self::$currentTable);
        return $this;
    }

    private function getCurrentTable(): ?string
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
        return $this->getStatementsHandler()->lastInsertId($name);
    }

    public function quote(mixed ...$params): string|int
    {
        return $this->getStatementsHandler()->quote(...$params);
    }

    private function insert(array $row): bool
    {
        $processor = new DataProcessor(self::$data, self::$schema);
        $result = $processor->insert($row);

        if ($result) {
            self::$data = $processor->getData();
            self::$connection = self::$data;

            if (!self::$inTransaction && CSV::getAttribute(CSV::ATTR_AUTO_SAVE)) {
                $this->save(self::$data);
            }

            $this->getStatementsHandler()->setLastInsertId(count(self::$data));
        }

        return $result;
    }

    private function update(array $data, array $where): int
    {
        $processor = new DataProcessor(self::$data, self::$schema);
        $affected = $processor->update($data, $where);

        if ($affected > 0) {
            self::$data = $processor->getData();
            self::$connection = self::$data;

            if (!self::$inTransaction && CSV::getAttribute(CSV::ATTR_AUTO_SAVE)) {
                $this->save(self::$data);
            }
        }

        $this->getStatementsHandler()->setAffectedRows($affected);
        return $affected;
    }

    private function delete(array $where): int
    {
        $processor = new DataProcessor(self::$data, self::$schema);
        $deleted = $processor->delete($where);

        if ($deleted > 0) {
            self::$data = $processor->getData();
            self::$connection = self::$data;

            if (!self::$inTransaction && CSV::getAttribute(CSV::ATTR_AUTO_SAVE)) {
                $this->save(self::$data);
            }
        }

        $this->getStatementsHandler()->setAffectedRows($deleted);
        return $deleted;
    }

    private function selectWhere(array $columns, array $where): array
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

    // IStatements implementation - delegated to handler
    public function setAllMetadata(): void
    {
        $this->getStatementsHandler()->setAllMetadata();
    }
    public function getAllMetadata(): object
    {
        return $this->getStatementsHandler()->getAllMetadata();
    }
    public function getQueryString(): string
    {
        return $this->getStatementsHandler()->getQueryString();
    }
    public function setQueryString(string $params): void
    {
        $this->getStatementsHandler()->setQueryString($params);
    }
    public function getQueryParameters(): ?array
    {
        return $this->getStatementsHandler()->getQueryParameters();
    }
    public function setQueryParameters(?array $params): void
    {
        $this->getStatementsHandler()->setQueryParameters($params);
    }
    public function getQueryRows(): int|false
    {
        return $this->getStatementsHandler()->getQueryRows();
    }
    public function setQueryRows(callable|int|false $params): void
    {
        $this->getStatementsHandler()->setQueryRows($params);
    }
    public function getQueryColumns(): int|false
    {
        return $this->getStatementsHandler()->getQueryColumns();
    }
    public function setQueryColumns(int|false $params): void
    {
        $this->getStatementsHandler()->setQueryColumns($params);
    }
    public function getAffectedRows(): int|false
    {
        return $this->getStatementsHandler()->getAffectedRows();
    }
    public function setAffectedRows(int|false $params): void
    {
        $this->getStatementsHandler()->setAffectedRows($params);
    }
    public function getStatement(): mixed
    {
        return $this->getStatementsHandler()->getStatement();
    }
    public function setStatement(mixed $statement): void
    {
        $this->getStatementsHandler()->setStatement($statement);
    }
    public function bindParam(object $params): void
    {
        $this->getStatementsHandler()->bindParam($params);
    }
    public function parse(mixed ...$params): string
    {
        return $this->getStatementsHandler()->parse(...$params);
    }
    public function query(mixed ...$params): static|null
    {
        $this->getStatementsHandler()->query(...$params);
        return $this;
    }
    public function prepare(mixed ...$params): static|null
    {
        $this->getStatementsHandler()->prepare(...$params);
        return $this;
    }
    public function exec(mixed ...$params): mixed
    {
        return $this->getStatementsHandler()->exec(...$params);
    }

    // IFetch implementation
    public function fetch(?int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): mixed
    {
        return $this->getFetchHandler()->fetch($fetchStyle, $fetchArgument, $optArgs);
    }

    public function fetchAll(?int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): array|bool
    {
        return $this->getFetchHandler()->fetchAll($fetchStyle, $fetchArgument, $optArgs);
    }

    public function getAttribute(mixed $name): mixed
    {
        return CSV::getAttribute($name);
    }
    public function setAttribute(mixed $name, mixed $value): void
    {
        CSV::setAttribute($name, $value);
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
