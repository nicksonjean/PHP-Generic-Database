<?php

declare(strict_types=1);

namespace GenericDatabase;

use GenericDatabase\IQueryBuilder;
use GenericDatabase\Shared\Singleton;
use GenericDatabase\Connection;
use GenericDatabase\Engine\FirebirdConnection;
use GenericDatabase\Engine\FirebirdQueryBuilder;
use GenericDatabase\Engine\OCIConnection;
use GenericDatabase\Engine\OCIQueryBuilder;
use GenericDatabase\Engine\PgSQLConnection;
use GenericDatabase\Engine\PgSQLQueryBuilder;
use GenericDatabase\Engine\MySQLiConnection;
use GenericDatabase\Engine\MySQLiQueryBuilder;
use GenericDatabase\Engine\SQLSrvConnection;
use GenericDatabase\Engine\SQLSrvQueryBuilder;
use GenericDatabase\Engine\SQLiteConnection;
use GenericDatabase\Engine\SQLiteQueryBuilder;
use GenericDatabase\Engine\PDOConnection;
use GenericDatabase\Engine\PDOQueryBuilder;
use GenericDatabase\Engine\ODBCConnection;
use GenericDatabase\Engine\ODBCQueryBuilder;
use Exception;

class QueryBuilder implements IQueryBuilder, IQueryBuilderStrategy
{
    use Singleton;

    /**
     * Property of the type object who define the connection
     */
    private static Connection $context;

    /**
     * Property of the type object who define the strategy
     */
    private IQueryBuilder $strategy;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->initContext();
        $this->initStrategy();
    }

    /**
     * Defines the strategy instance
     *
     * @param IQueryBuilder $strategy
     * @return void
     */
    public function setStrategy(IQueryBuilder $strategy): void
    {
        $this->strategy = $strategy;
    }

    /**
     * Get the strategy instance
     *
     * @return IQueryBuilder
     */
    public function getStrategy(): IQueryBuilder
    {
        return $this->strategy;
    }

    /**
     * Factory that replaces the __constructor and defines the Strategy through the engine parameter
     *
     * @return void
     * @throws Exception
     */
    private function initContext(): void
    {
        self::$context = new Connection();

        $connections = [
            FirebirdConnection::class,
            MySQLiConnection::class,
            OCIConnection::class,
            PgSQLConnection::class,
            SQLSrvConnection::class,
            SQLiteConnection::class,
            PDOConnection::class,
            ODBCConnection::class
        ];

        $instance = null;

        foreach ($connections as $connection) {
            $instanceCandidate = $connection::getInstance();
            if ($instanceCandidate instanceof $connection) {
                $instance = $instanceCandidate;
                break;
            }
        }

        if ($instance === null) {
            throw new Exception('No valid context found');
        }

        self::$context->setStrategy($instance);
    }

    /**
     * Factory that replaces the __constructor and defines the Strategy through the engine parameter
     *
     * @return void
     * @throws Exception
     */
    private function initStrategy(): void
    {
        $engine = self::$context->getEngine();

        if (!is_string($engine)) {
            throw new Exception('Engine must be a string');
        }

        $strategy = match ($engine) {
            'firebird' => FirebirdQueryBuilder::getInstance(),
            'mysqli' => MySQLiQueryBuilder::getInstance(),
            'oci' => OCIQueryBuilder::getInstance(),
            'pgsql' => PgSQLQueryBuilder::getInstance(),
            'sqlsrv' => SQLSrvQueryBuilder::getInstance(),
            'sqlite' => SQLiteQueryBuilder::getInstance(),
            'pdo' => PDOQueryBuilder::getInstance(),
            'odbc' => ODBCQueryBuilder::getInstance(),
            default => null,
        };

        if ($strategy === null) {
            throw new Exception('No valid strategy found');
        }

        $this->setStrategy($strategy);
    }

    public static function select(array|string ...$data): IQueryBuilder
    {
        self::getInstance()->getStrategy()->select(...$data);
        return self::getInstance();
    }

    public static function distinct(array|string ...$data): IQueryBuilder
    {
        self::getInstance()->getStrategy()->distinct(...$data);
        return self::getInstance();
    }

    public static function from(array|string ...$data): IQueryBuilder
    {
        self::getInstance()->getStrategy()->from(...$data);
        return self::getInstance();
    }

    public static function join(array|string ...$data): IQueryBuilder
    {
        self::getInstance()->getStrategy()->join(...$data);
        return self::getInstance();
    }

    public static function selfJoin(array|string ...$data): IQueryBuilder
    {
        self::getInstance()->getStrategy()->selfJoin(...$data);
        return self::getInstance();
    }

    public static function leftJoin(array|string ...$data): IQueryBuilder
    {
        self::getInstance()->getStrategy()->leftJoin(...$data);
        return self::getInstance();
    }

    public static function rightJoin(array|string ...$data): IQueryBuilder
    {
        self::getInstance()->getStrategy()->rightJoin(...$data);
        return self::getInstance();
    }

    public static function innerJoin(array|string ...$data): IQueryBuilder
    {
        self::getInstance()->getStrategy()->innerJoin(...$data);
        return self::getInstance();
    }

    public static function outerJoin(array|string ...$data): IQueryBuilder
    {
        self::getInstance()->getStrategy()->outerJoin(...$data);
        return self::getInstance();
    }

    public static function crossJoin(array|string ...$data): IQueryBuilder
    {
        self::getInstance()->getStrategy()->crossJoin(...$data);
        return self::getInstance();
    }

    public static function on(array|string ...$data): IQueryBuilder
    {
        self::getInstance()->getStrategy()->on(...$data);
        return self::getInstance();
    }

    public static function andOn(array|string ...$data): IQueryBuilder
    {
        self::getInstance()->getStrategy()->andOn(...$data);
        return self::getInstance();
    }

    public static function orOn(array|string ...$data): IQueryBuilder
    {
        self::getInstance()->getStrategy()->orOn(...$data);
        return self::getInstance();
    }

    public static function where(array|string ...$data): IQueryBuilder
    {
        self::getInstance()->getStrategy()->where(...$data);
        return self::getInstance();
    }

    public static function andWhere(array|string ...$data): IQueryBuilder
    {
        self::getInstance()->getStrategy()->andWhere(...$data);
        return self::getInstance();
    }

    public static function orWhere(array|string ...$data): IQueryBuilder
    {
        self::getInstance()->getStrategy()->andWhere(...$data);
        return self::getInstance();
    }

    public static function having(array|string ...$data): IQueryBuilder
    {
        self::getInstance()->getStrategy()->having(...$data);
        return self::getInstance();
    }

    public static function andHaving(array|string ...$data): IQueryBuilder
    {
        self::getInstance()->getStrategy()->andHaving(...$data);
        return self::getInstance();
    }

    public static function orHaving(array|string ...$data): IQueryBuilder
    {
        self::getInstance()->getStrategy()->orHaving(...$data);
        return self::getInstance();
    }

    public static function group(array|string ...$data): IQueryBuilder
    {
        self::getInstance()->getStrategy()->group(...$data);
        return self::getInstance();
    }

    public static function order(array|string ...$data): IQueryBuilder
    {
        self::getInstance()->getStrategy()->order(...$data);
        return self::getInstance();
    }

    public static function orderAsc(array|string ...$data): IQueryBuilder
    {
        self::getInstance()->getStrategy()->orderAsc(...$data);
        return self::getInstance();
    }

    public static function orderDesc(array|string ...$data): IQueryBuilder
    {
        self::getInstance()->getStrategy()->orderDesc(...$data);
        return self::getInstance();
    }

    public static function limit(array|string ...$data): IQueryBuilder
    {
        self::getInstance()->getStrategy()->limit(...$data);
        return self::getInstance();
    }

    public function build(): string
    {
        return self::getInstance()->getStrategy()->build();
    }

    public function buildRaw(): string
    {
        return self::getInstance()->getStrategy()->buildRaw();
    }

    public function getValues(): array
    {
        return self::getInstance()->getStrategy()->getValues();
    }

    public function getAllMetadata(): object
    {
        return self::getInstance()->getStrategy()->getAllMetadata();
    }

    public function fetch(int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): mixed
    {
        return self::getInstance()->getStrategy()->fetch($fetchStyle, $fetchArgument, $optArgs);
    }

    public function fetchAll(int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): array|bool
    {
        return self::getInstance()->getStrategy()->fetchAll($fetchStyle, $fetchArgument, $optArgs);
    }
}
