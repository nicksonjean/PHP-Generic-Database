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
use GenericDatabase\Engine\INI\Connection\INI;
use GenericDatabase\Interfaces\Connection\IDSN;
use GenericDatabase\Interfaces\Connection\IOptions;
use GenericDatabase\Interfaces\Connection\IArguments;
use GenericDatabase\Interfaces\Connection\IAttributes;
use GenericDatabase\Interfaces\Connection\IStructure;
use GenericDatabase\Interfaces\Connection\IReport;
use GenericDatabase\Interfaces\Connection\IFlatFileFetch;
use GenericDatabase\Interfaces\Connection\IFlatFileStatements;
use GenericDatabase\Helpers\Parsers\QueryTypeDetector;
use GenericDatabase\Interfaces\Connection\ITransactions;
use GenericDatabase\Engine\INI\Connection\DSN\DSNHandler;
use GenericDatabase\Engine\INI\Connection\Fetch\FetchHandler;
use GenericDatabase\Engine\INI\Connection\Report\ReportHandler;
use GenericDatabase\Engine\INI\Connection\Options\OptionsHandler;
use GenericDatabase\Engine\INI\Connection\Arguments\ArgumentsHandler;
use GenericDatabase\Engine\INI\Connection\Attributes\AttributesHandler;
use GenericDatabase\Engine\INI\Connection\Fetch\Strategy\FetchStrategy;
use GenericDatabase\Engine\INI\Connection\Arguments\Strategy\ArgumentsStrategy;
use GenericDatabase\Engine\INI\Connection\Statements\StatementsHandler;
use GenericDatabase\Engine\INI\Connection\Transactions\TransactionsHandler;
use GenericDatabase\Engine\INI\Connection\Structure\StructureHandler;
use GenericDatabase\Engine\INI\Connection\Structure\Strategy\StructureStrategy;

/**
 * INI Connection class for flat file database operations.
 * Provides SQL-like operations on INI files with Schema.ini support.
 * Uses StructureHandler for load/save (same architecture as INIConnection).
 *
 * @method static INIConnection|void setDatabase(mixed $value) Sets the database (directory) path.
 * @method static INIConnection|string getDatabase($value = null) Retrieves the database path.
 */
#[AllowDynamicProperties]
class INIConnection implements IConnection
{
    use Methods;
    use Singleton;

    private static mixed $connection = null;
    private static IFlatFileFetch $fetchHandler;
    private static IFlatFileStatements $statementsHandler;
    private static IDSN $dsnHandler;
    private static IAttributes $attributesHandler;
    private static IOptions $optionsHandler;
    private static IArguments $argumentsHandler;
    private static ITransactions $transactionsHandler;
    private static IStructure $structureHandler;
    private static IReport $reportHandler;
    private static string $engine = 'ini';

    public function __construct()
    {
        self::$structureHandler = new StructureHandler($this, new StructureStrategy());
        self::$fetchHandler = new FetchHandler($this, new FetchStrategy(), self::$structureHandler);
        self::$optionsHandler = new OptionsHandler($this);
        self::$dsnHandler = new DSNHandler($this);
        self::$reportHandler = new ReportHandler($this);
        self::$statementsHandler = new StatementsHandler($this, self::$structureHandler, self::$optionsHandler, self::$reportHandler);
        self::$attributesHandler = new AttributesHandler($this, self::$optionsHandler);
        self::$argumentsHandler = new ArgumentsHandler($this, self::$optionsHandler, new ArgumentsStrategy());
        self::$transactionsHandler = new TransactionsHandler($this, self::$structureHandler);
    }

    private function getReportHandler(): IReport
    {
        return self::$reportHandler;
    }

    private function getStructureHandler(): IStructure
    {
        return self::$structureHandler;
    }

    private function getFetchHandler(): IFlatFileFetch
    {
        return self::$fetchHandler;
    }

    private function getStatementsHandler(): IFlatFileStatements
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

    public function __call(string $name, array $arguments): IConnection|string|int|bool|array|null
    {
        return $this->getArgumentsHandler()->__call($name, $arguments);
    }

    public static function __callStatic(string $name, array $arguments): IConnection|string|int|bool|array|null
    {
        return self::getInstance()->getArgumentsHandler()->__callStatic($name, $arguments);
    }

    private function preConnect(): INIConnection
    {
        $this->getOptionsHandler()->setOptions(static::getOptions());
        static::setOptions($this->getOptionsHandler()->getOptions());
        static::setStructure($this->getStructureHandler()->mount());
        static::setDsn($this->parseDsn());
        return $this;
    }

    private function postConnect(): INIConnection
    {
        $this->getOptionsHandler()->define();
        $this->getAttributesHandler()->define();
        return $this;
    }

    private function parseDsn(): string|Exceptions
    {
        return $this->getDsnHandler()->parse();
    }

    private function realConnect(string $database): INIConnection
    {
        try {
            $schemaJson = __DIR__ . '/INI/Connection/INI.json';
            $schemaParser = new SchemaParser($schemaJson);
            $validJson = $schemaParser->parse([
                'database' => $database,
                'charset' => static::getCharset() ?? 'UTF-8'
            ]);
            $validator = new SchemaValidator($schemaJson);

            if ($validator->validate($validJson)) {
                $isMemory = $database === 'memory';

                if (!$isMemory) {
                    if (!is_dir($database)) {
                        $projectRoot = defined('PATH_ROOT') ? constant('PATH_ROOT') : getcwd();
                        $potentialPath = realpath($projectRoot . DIRECTORY_SEPARATOR . $database);

                        if ($potentialPath !== false && is_dir($potentialPath)) {
                            static::setDatabase($potentialPath);
                            $database = $potentialPath;
                        } elseif (!mkdir($database, 0755, true) && !is_dir($database)) {
                            throw new Exceptions("Database directory does not exist and could not be created: " . $database);
                        }
                    }
                }

                self::$connection = $this->getStructureHandler()->getData();
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

    public function connect(): INIConnection
    {
        try {
            $this->setInstance($this);
            $this
                ->preConnect()
                ->getInstance()
                ->realConnect(static::getDatabase())
                ->postConnect()
                ->setConnected(true);
            return $this;
        } catch (Exception $error) {
            $this->disconnect();
            throw new Exceptions($error->getMessage());
        }
    }

    public function ping(): bool
    {
        $database = static::getDatabase();
        if ($database === 'memory') {
            return $this->getInstance()->getConnected();
        }
        return is_dir($database);
    }

    public function disconnect(): void
    {
        if ($this->isConnected()) {
            static::setConnected(false);
            $this->setConnection(null);
            $this->getStructureHandler()->reset();
        }
    }

    public function isConnected(): bool
    {
        $database = static::getDatabase();
        if ($database === 'memory') {
            return $this->getInstance()->getConnected();
        }
        return is_dir($database) && $this->getInstance()->getConnected();
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

    public function beginTransaction(): bool
    {
        return $this->getTransactionsHandler()->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->getTransactionsHandler()->commit();
    }

    public function rollback(): bool
    {
        return $this->getTransactionsHandler()->rollback();
    }

    public function inTransaction(): bool
    {
        return $this->getTransactionsHandler()->inTransaction();
    }

    public function lastInsertId(?string $name = null): string|int|false
    {
        return $this->getStatementsHandler()->lastInsertId($name);
    }

    public function quote(mixed ...$params): string|int
    {
        return $this->getStatementsHandler()->quote(...$params);
    }

    public function setAllMetadata(): void
    {
        $this->getStatementsHandler()->setAllMetadata();
    }

    public function getAllMetadata(): object
    {
        $queryString = $this->getStatementsHandler()->getQueryString();

        if ($this->getStatementsHandler()->getQueryRows() === 0 && !empty($queryString) && QueryTypeDetector::isDmlQuery($queryString) === false) {
            $this->getFetchHandler()->execute();
        }

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
        $this->getFetchHandler()->clearCache();
        $this->getStatementsHandler()->query(...$params);
        return $this;
    }

    public function prepare(mixed ...$params): static|null
    {
        $this->getFetchHandler()->clearCache();
        $this->getStatementsHandler()->prepare(...$params);
        return $this;
    }

    public function exec(mixed ...$params): mixed
    {
        return $this->getStatementsHandler()->exec(...$params);
    }

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
        return INI::getAttribute($name);
    }

    public function setAttribute(mixed $name, mixed $value): void
    {
        INI::setAttribute($name, $value);
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
