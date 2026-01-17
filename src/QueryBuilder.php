<?php

declare(strict_types=1);

namespace GenericDatabase;

use GenericDatabase\Interfaces\IQueryBuilder;
use GenericDatabase\Interfaces\Strategy\IQueryBuilderStrategy;
use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Shared\Run;
use GenericDatabase\Shared\Singleton;
use GenericDatabase\Engine\FirebirdQueryBuilder;
use GenericDatabase\Engine\OCIQueryBuilder;
use GenericDatabase\Engine\PgSQLQueryBuilder;
use GenericDatabase\Engine\MySQLiQueryBuilder;
use GenericDatabase\Engine\SQLSrvQueryBuilder;
use GenericDatabase\Engine\SQLiteQueryBuilder;
use GenericDatabase\Engine\PDOQueryBuilder;
use GenericDatabase\Engine\ODBCQueryBuilder;
use GenericDatabase\Engine\JSONQueryBuilder;
use GenericDatabase\Engine\CSVQueryBuilder;
use GenericDatabase\Engine\XMLQueryBuilder;
use GenericDatabase\Engine\YAMLQueryBuilder;
use Exception;

/**
 * The `QueryBuilder` class implements the `IQueryBuilder` and `IQueryBuilderStrategy` interfaces to provide a flexible query building mechanism using various database engines.
 * Utilizes the Singleton pattern to ensure a single instance. The class allows setting and retrieving query strategies based on the database engine, and provides methods for
 * constructing SQL queries with various clauses such as SELECT, JOIN, WHERE, and ORDER. It also supports fetching results and metadata.
 * It uses the Singleton pattern to ensure a single instance and provides a flexible query building mechanism using various database engines.
 *
 * Methods:
 * - `__construct(IConnection $context = null)`: Initializes the query builder with a database connection context.
 * - `with(IConnection $context)`: Static initializer that sets the database connection context and returns the query builder instance.
 * - `setStrategy(IQueryBuilder $strategy)`: Sets the query strategy instance.
 * - `getStrategy()`: Returns the query strategy instance.
 *
 * Query Building Methods:
 * - `select(array|string ...$data)`: Adds a SELECT clause to the query.
 * - `distinct(array|string ...$data)`: Adds a DISTINCT clause to the query.
 * - `from(array|string ...$data)`: Adds a FROM clause to the query.
 * - `join(array|string ...$data)`: Adds a JOIN clause to the query.
 * - `selfJoin(array|string ...$data)`: Adds a SELF JOIN clause to the query.
 * - `leftJoin(array|string ...$data)`: Adds a LEFT JOIN clause to the query.
 * - `rightJoin(array|string ...$data)`: Adds a RIGHT JOIN clause to the query.
 * - `innerJoin(array|string ...$data)`: Adds an INNER JOIN clause to the query.
 * - `outerJoin(array|string ...$data)`: Adds an OUTER JOIN clause to the query.
 * - `crossJoin(array|string ...$data)`: Adds a CROSS JOIN clause to the query.
 * - `on(array|string ...$data)`: Adds an ON clause to the query.
 * - `andOn(array|string ...$data)`: Adds an AND ON clause to the query.
 * - `orOn(array|string ...$data)`: Adds an OR ON clause to the query.
 * - `where(array|string ...$data)`: Adds a WHERE clause to the query.
 * - `andWhere(array|string ...$data)`: Adds an AND WHERE clause to the query.
 * - `orWhere(array|string ...$data)`: Adds an OR WHERE clause to the query.
 * - `having(array|string ...$data)`: Adds a HAVING clause to the query.
 * - `andHaving(array|string ...$data)`: Adds an AND HAVING clause to the query.
 * - `orHaving(array|string ...$data)`: Adds an OR HAVING clause to the query.
 * - `group(array|string ...$data)`: Adds a GROUP BY clause to the query.
 * - `order(array|string ...$data)`: Adds an ORDER BY clause to the query.
 * - `orderAsc(array|string ...$data)`: Adds an ORDER BY ASC clause to the query.
 * - `orderDesc(array|string ...$data)`: Adds an ORDER BY DESC clause to the query.
 * - `limit(array|string ...$data)`: Adds a LIMIT clause to the query.
 *
 * Query Execution Methods:
 * - `build()`: Builds the query string.
 * - `buildRaw()`: Builds the raw query string.
 * - `getValues()`: Returns the query values.
 * - `getAllMetadata()`: Returns the query metadata.
 * - `fetch(int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null)`: Fetches a single row from the query result.
 * - `fetchAll(int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null)`: Fetches all rows from the query result.
 *
 * Properties:
 * - `$context`: The database connection context.
 * - `$self`: The singleton instance of the QueryBuilder.
 * - `$strategy`: The current query strategy.
 */
class QueryBuilder implements IQueryBuilder, IQueryBuilderStrategy
{
    use Singleton;

    /**
     * Property of the type object who define the connection
     * @var IConnection|null $context
     */
    private static ?IConnection $context = null;

    /**
     * The singleton instance of the QueryBuilder.
     * @var QueryBuilder $self
     */
    private static QueryBuilder $self;

    /**
     * Property of the type object who define the strategy
     * @var IQueryBuilder $strategy
     */
    private IQueryBuilder $strategy;

    /**
     * Initializes the query builder with a database connection context.
     *
     * @param IConnection|null $context
     * @throws Exception
     */
    public function __construct(?IConnection $context = null)
    {
        self::$context = $context;
        self::$self = $this;
        $this->initStrategy();
    }

    /**
     * Static initializer that sets the database connection context and returns the query builder instance.
     *
     * @param IConnection|null $context
     * @return self
     * @throws Exception
     */
    public static function with(?IConnection $context = null): self
    {
        self::$context = $context;
        self::$self = new static($context);
        return self::$self;
    }

    /**
     * Sets the query strategy instance.
     *
     * @param IQueryBuilder $strategy
     * @return void
     */
    public function setStrategy(IQueryBuilder $strategy): void
    {
        $this->strategy = $strategy;
    }

    /**
     * Returns the query strategy instance.
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
    private function initStrategy(): void
    {
        $engine = Run::call([self::$context, 'getEngine']);

        if (!is_string($engine)) {
            throw new Exception('Engine must be a string');
        }

        $strategy = match ($engine) {
            'firebird' => (new FirebirdQueryBuilder(self::$context)),
            'mysqli' => (new MySQLiQueryBuilder(self::$context)),
            'oci' => (new OCIQueryBuilder(self::$context)),
            'pgsql' => (new PgSQLQueryBuilder(self::$context)),
            'sqlsrv' => (new SQLSrvQueryBuilder(self::$context)),
            'sqlite' => (new SQLiteQueryBuilder(self::$context)),
            'pdo' => (new PDOQueryBuilder(self::$context)),
            'odbc' => (new ODBCQueryBuilder(self::$context)),
            'json' => (new JSONQueryBuilder(self::$context)),
            'csv' => (new CSVQueryBuilder(self::$context)),
            'xml' => (new XMLQueryBuilder(self::$context)),
            'yaml' => (new YAMLQueryBuilder(self::$context)),
            default => null,
        };

        if ($strategy === null) {
            throw new Exception('No valid strategy found');
        }

        $this->setStrategy($strategy);
    }

    /**
     * Adds a SELECT clause to the query
     *
     * @param array|string[] $data
     * @return QueryBuilder
     */
    public static function select(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->select(...$data);
        return self::$self;
    }

    /**
     * Adds a DISTINCT clause to the query
     *
     * @param array|string[] $data
     * @return QueryBuilder
     */
    public static function distinct(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->distinct(...$data);
        return self::$self;
    }

    /**
     * Adds a FROM clause to the query
     *
     * @param array|string[] $data
     * @return QueryBuilder
     */
    public static function from(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->from(...$data);
        return self::$self;
    }

    /**
     * Adds a JOIN clause to the query
     *
     * @param array|string[] $data
     * @return QueryBuilder
     */
    public static function join(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->join(...$data);
        return self::$self;
    }

    /**
     * Adds a SELF JOIN clause to the query
     *
     * @param array|string[] $data
     * @return QueryBuilder
     */
    public static function selfJoin(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->selfJoin(...$data);
        return self::$self;
    }

    /**
     * Adds a LEFT JOIN clause to the query
     *
     * @param array|string[] $data
     * @return QueryBuilder
     */
    public static function leftJoin(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->leftJoin(...$data);
        return self::$self;
    }

    /**
     * Adds a RIGHT JOIN clause to the query
     *
     * @param array|string[] $data
     * @return QueryBuilder
     */
    public static function rightJoin(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->rightJoin(...$data);
        return self::$self;
    }

    /**
     * Adds a INNER JOIN clause to the query
     *
     * @param array|string[] $data
     * @return QueryBuilder
     */
    public static function innerJoin(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->innerJoin(...$data);
        return self::$self;
    }

    /**
     * Adds a OUTER JOIN clause to the query
     *
     * @param array|string[] $data
     * @return QueryBuilder
     */
    public static function outerJoin(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->outerJoin(...$data);
        return self::$self;
    }

    /**
     * Adds a CROSS JOIN clause to the query
     *
     * @param array|string[] $data
     * @return QueryBuilder
     */
    public static function crossJoin(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->crossJoin(...$data);
        return self::$self;
    }

    /**
     * Adds a ON clause to the query
     *
     * @param array|string[] $data
     * @return QueryBuilder
     */
    public static function on(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->on(...$data);
        return self::$self;
    }

    /**
     * Adds a AND ON clause to the query
     * @param array|string[] $data
     * @return QueryBuilder
     */
    public static function andOn(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->andOn(...$data);
        return self::$self;
    }

    /**
     * Adds a OR ON clause to the query
     *
     * @param array|string[] $data
     * @return QueryBuilder
     */
    public static function orOn(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->orOn(...$data);
        return self::$self;
    }

    /**
     * Adds a WHERE clause to the query
     *
     * @param array|string[] $data
     * @return QueryBuilder
     */
    public static function where(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->where(...$data);
        return self::$self;
    }

    /**
     * Adds a AND WHERE clause to the query
     *
     * @param array|string[] $data
     * @return QueryBuilder
     */
    public static function andWhere(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->andWhere(...$data);
        return self::$self;
    }

    /**
     * Adds a OR WHERE clause to the query
     *
     * @param array|string[] $data
     * @return QueryBuilder
     */
    public static function orWhere(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->andWhere(...$data);
        return self::$self;
    }

    /**
     * Adds a HAVING clause to the query
     *
     * @param array|string[] $data
     * @return QueryBuilder
     */
    public static function having(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->having(...$data);
        return self::$self;
    }

    /**
     * Adds a AND HAVING clause to the query
     *
     * @param array|string[] $data
     * @return QueryBuilder
     */
    public static function andHaving(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->andHaving(...$data);
        return self::$self;
    }

    /**
     * Adds a OR HAVING clause to the query
     *
     * @param array|string[] $data
     * @return QueryBuilder
     */
    public static function orHaving(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->orHaving(...$data);
        return self::$self;
    }

    /**
     * Adds a GROUP clause to the query
     *
     * @param array|string[] $data
     * @return QueryBuilder
     */
    public static function group(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->group(...$data);
        return self::$self;
    }

    /**
     * Adds a ORDER clause to the query
     *
     * @param array|string[] $data
     * @return QueryBuilder
     */
    public static function order(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->order(...$data);
        return self::$self;
    }

    /**
     * Adds a GROUP ASC clause to the query
     *
     * @param array|string[] $data
     * @return QueryBuilder
     */
    public static function orderAsc(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->orderAsc(...$data);
        return self::$self;
    }

    /**
     * Adds a ORDER DESC clause to the query
     *
     * @param array|string[] $data
     * @return QueryBuilder
     */
    public static function orderDesc(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->orderDesc(...$data);
        return self::$self;
    }

    /**
     * Adds a LIMIT clause to the query
     *
     * @param array|string[] $data
     * @return QueryBuilder
     */
    public static function limit(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->limit(...$data);
        return self::$self;
    }

    /**
     * Summary of build
     * @return string
     */
    public function build(): string
    {
        return self::$self->getStrategy()->build();
    }

    /**
     * Summary of buildRaw
     * @return string
     */
    public function buildRaw(): string
    {
        return self::$self->getStrategy()->buildRaw();
    }

    /**
     * Returns the query values
     *
     * @return array
     */
    public function getValues(): array
    {
        return self::$self->getStrategy()->getValues();
    }

    /**
     * Returns the query metadata
     *
     * @return object
     */
    public function getAllMetadata(): object
    {
        return self::$self->getStrategy()->getAllMetadata();
    }

    /**
     * Fetches a single row from the query result.
     *
     * @param int|null $fetchStyle
     * @param mixed $fetchArgument
     * @param mixed $optArgs
     * @return mixed
     */
    public function fetch(?int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): mixed
    {
        return self::$self->getStrategy()->fetch($fetchStyle, $fetchArgument, $optArgs);
    }

    /**
     * Fetches all rows from the query result.
     *
     * @param int|null $fetchStyle
     * @param mixed $fetchArgument
     * @param mixed $optArgs
     * @return array|bool
     */
    public function fetchAll(?int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): array|bool
    {
        return self::$self->getStrategy()->fetchAll($fetchStyle, $fetchArgument, $optArgs);
    }
}
