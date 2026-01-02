<?php

namespace GenericDatabase\Engine;

use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Interfaces\IQueryBuilder;
use GenericDatabase\Core\Join;
use GenericDatabase\Core\Where;
use GenericDatabase\Core\Having;
use GenericDatabase\Core\Select;
use GenericDatabase\Core\Sorting;
use GenericDatabase\Core\Grouping;
use GenericDatabase\Core\Junction;
use GenericDatabase\Core\Condition;
use GenericDatabase\Helpers\Types\Compounds\Arrays;
use GenericDatabase\Shared\Singleton;
use GenericDatabase\Helpers\Parsers\SQL;
use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Generic\QueryBuilder\Query;
use GenericDatabase\Generic\QueryBuilder\Context;
use GenericDatabase\Engine\SQLite\QueryBuilder\Builder;
use GenericDatabase\Engine\SQLite\QueryBuilder\Clause;

/**
 * The `SQLiteQueryBuilder` class implements the `IQueryBuilder` interfaces to provide a flexible query building mechanism using various database engines.
 * Utilizes the Singleton pattern to ensure a single instance. The class allows setting and retrieving query strategies based on the database engine, and provides methods for
 * constructing SQL queries with various clauses such as SELECT, JOIN, WHERE, and ORDER. It also supports fetching results and metadata.
 * It uses the Singleton pattern to ensure a single instance and provides a flexible query building mechanism using various database engines.
 *
 * Methods:
 * - `__construct(IConnection $context = null)`: Initializes the query builder with a database connection context.
 * - `with(IConnection $context)`: Static initializer that sets the database connection context and returns the query builder instance.
 * - `setContext(IQueryBuilder $context)`: Sets the query context instance.
 * - `getContext()`: Returns the query context instance.
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
 * - `$lastQuery`: The last query executed.
 * - `$cursorExhausted`: The current query cursor exhausted status.
 */
class SQLiteQueryBuilder implements IQueryBuilder
{
    use Query;
    use Context;
    use Singleton;

    private static SQLiteQueryBuilder $self;

    private static ?string $lastQuery = null;

    private static bool $cursorExhausted = false;

    public function __construct(?IConnection $context = null)
    {
        $this->initQuery();
        self::$context = $context;
        self::$self = $this;
    }

    /**
     * Static initializer with context
     *
     * @param IConnection $context
     * @return self
     */
    public static function with(IConnection $context): self
    {
        self::$context = $context;
        self::$self = new static($context);
        return self::$self;
    }

    /**
     * @param array|string ...$data
     * @return static
     */
    public static function select(array|string ...$data): static
    {
        /** @var static */
        return Clause::select(['type' => Select::DEFAULT(), 'data' => $data, 'self' => self::$self]);
    }

    /**
     * @param array|string ...$data
     * @return static
     */
    public static function distinct(array|string ...$data): static
    {
        /** @var static */
        return Clause::select(['type' => Select::DISTINCT(), 'data' => $data, 'self' => self::$self]);
    }

    /**
     * @param array|string ...$data
     * @return static
     */
    public static function from(array|string ...$data): static
    {
        /** @var static */
        return Clause::from(['data' => $data, 'self' => self::$self]);
    }

    /**
     * @param array|string ...$data
     * @return static
     */
    public static function join(array|string ...$data): static
    {
        /** @var static */
        return Clause::join(
            ['type' => Join::DEFAULT(), 'junction' => Junction::NONE(), 'data' => $data, 'self' => self::$self]
        );
    }

    /**
     * @param array|string ...$data
     * @return static
     */
    public static function selfJoin(array|string ...$data): static
    {
        /** @var static */
        return Clause::join(
            ['type' => Join::SELF(), 'junction' => Junction::NONE(), 'data' => $data, 'self' => self::$self]
        );
    }

    /**
     * @param array|string ...$data
     * @return static
     */
    public static function leftJoin(array|string ...$data): static
    {
        /** @var static */
        return Clause::join(
            ['type' => Join::LEFT(), 'junction' => Junction::NONE(), 'data' => $data, 'self' => self::$self]
        );
    }

    /**
     * @param array|string ...$data
     * @return static
     */
    public static function rightJoin(array|string ...$data): static
    {
        /** @var static */
        return Clause::join(
            ['type' => Join::RIGHT(), 'junction' => Junction::NONE(), 'data' => $data, 'self' => self::$self]
        );
    }

    /**
     * @param array|string ...$data
     * @return static
     */
    public static function innerJoin(array|string ...$data): static
    {
        /** @var static */
        return Clause::join(
            ['type' => Join::INNER(), 'junction' => Junction::NONE(), 'data' => $data, 'self' => self::$self]
        );
    }

    /**
     * @param array|string ...$data
     * @return static
     */
    public static function outerJoin(array|string ...$data): static
    {
        /** @var static */
        return Clause::join(
            ['type' => Join::OUTER(), 'junction' => Junction::NONE(), 'data' => $data, 'self' => self::$self]
        );
    }

    /**
     * @param array|string ...$data
     * @return static
     */
    public static function crossJoin(array|string ...$data): static
    {
        /** @var static */
        return Clause::join(
            ['type' => Join::CROSS(), 'junction' => Junction::NONE(), 'data' => $data, 'self' => self::$self]
        );
    }

    /**
     * @param array|string ...$data
     * @return static
     */
    public static function on(array|string ...$data): static
    {
        /** @var static */
        return Clause::on(['junction' => Junction::NONE(), 'data' => $data, 'self' => self::$self]);
    }

    /**
     * @param array|string ...$data
     * @return static
     */
    public static function andOn(array|string ...$data): static
    {
        /** @var static */
        return Clause::on(['junction' => Junction::CONJUNCTION(), 'data' => $data, 'self' => self::$self]);
    }

    /**
     * @param array|string ...$data
     * @return static
     */
    public static function orOn(array|string ...$data): static
    {
        /** @var static */
        return Clause::on(['junction' => Junction::DISJUNCTION(), 'data' => $data, 'self' => self::$self]);
    }

    /**
     * @param array|string ...$data
     * @return static
     */
    public static function where(array|string ...$data): static
    {
        if (Arrays::isDepthArray($data) > 2) {
            $result = [];
            foreach (reset($data) as $value) {
                $condition = Condition::NONE();
                $keys = array_keys($value)[0];
                $values = array_values($value);
                if (is_string($keys)) {
                    $condition = $keys === 'AND' ? Condition::CONJUNCTION() : Condition::DISJUNCTION();
                }
                $result[] = [
                    'enum' => Where::class,
                    'condition' => $condition,
                    'data' => [$values],
                    'self' => self::$self
                ];
            }
            /** @var static */
            return Clause::where($result);
        } else {
            /** @var static */
            return Clause::where([
                'enum' => Where::class,
                'condition' => Condition::NONE(),
                'data' => $data,
                'self' => self::$self
            ]);
        }
    }

    /**
     * @param array|string ...$data
     * @return static
     */
    public static function andWhere(array|string ...$data): static
    {
        if (Arrays::isDepthArray($data) > 2) {
            $result = [];
            foreach (reset($data) as $value) {
                $condition = Condition::CONJUNCTION();
                $keys = array_keys($value)[0];
                $values = array_values($value);
                if (is_string($keys)) {
                    $condition = $keys === 'AND' ? Condition::CONJUNCTION() : Condition::DISJUNCTION();
                }
                $result[] = [
                    'enum' => Where::class,
                    'condition' => $condition,
                    'data' => [$values],
                    'self' => self::$self
                ];
            }
            /** @var static */
            return Clause::where($result);
        } else {
            /** @var static */
            return Clause::where([
                'enum' => Where::class,
                'condition' => Condition::CONJUNCTION(),
                'data' => $data,
                'self' => self::$self
            ]);
        }
    }

    /**
     * @param array|string ...$data
     * @return static
     */
    public static function orWhere(array|string ...$data): static
    {
        if (Arrays::isDepthArray($data) > 2) {
            $result = [];
            foreach (reset($data) as $value) {
                $condition = Condition::DISJUNCTION();
                $keys = array_keys($value)[0];
                $values = array_values($value);
                if (is_string($keys)) {
                    $condition = $keys === 'AND' ? Condition::CONJUNCTION() : Condition::DISJUNCTION();
                }
                $result[] = [
                    'enum' => Where::class,
                    'condition' => $condition,
                    'data' => [$values],
                    'self' => self::$self
                ];
            }
            /** @var static */
            return Clause::where($result);
        } else {
            /** @var static */
            return Clause::where([
                'enum' => Where::class,
                'condition' => Condition::DISJUNCTION(),
                'data' => $data,
                'self' => self::$self
            ]);
        }
    }

    /**
     * @param array|string ...$data
     * @return static
     */
    public static function having(array|string ...$data): static
    {
        if (Arrays::isDepthArray($data) > 2) {
            $result = [];
            foreach (reset($data) as $value) {
                $condition = Condition::NONE();
                $keys = array_keys($value)[0];
                $values = array_values($value);
                if (is_string($keys)) {
                    $condition = $keys === 'AND' ? Condition::CONJUNCTION() : Condition::DISJUNCTION();
                }
                $result[] = [
                    'enum' => Having::class,
                    'condition' => $condition,
                    'data' => [$values],
                    'self' => self::$self
                ];
            }
            /** @var static */
            return Clause::having($result);
        } else {
            /** @var static */
            return Clause::having([
                'enum' => Having::class,
                'condition' => Condition::NONE(),
                'data' => $data,
                'self' => self::$self
            ]);
        }
    }

    /**
     * @param array|string ...$data
     * @return static
     */
    public static function andHaving(array|string ...$data): static
    {
        if (Arrays::isDepthArray($data) > 2) {
            $result = [];
            foreach (reset($data) as $value) {
                $result[] = [
                    'enum' => Having::class,
                    'condition' => Condition::CONJUNCTION(),
                    'data' => [$value],
                    'self' => self::$self
                ];
            }
            /** @var static */
            return Clause::having($result);
        } else {
            /** @var static */
            return Clause::having([
                'enum' => Having::class,
                'condition' => Condition::CONJUNCTION(),
                'data' => $data,
                'self' => self::$self
            ]);
        }
    }

    /**
     * @param array|string ...$data
     * @return static
     */
    public static function orHaving(array|string ...$data): static
    {
        if (Arrays::isDepthArray($data) > 2) {
            $result = [];
            foreach (reset($data) as $value) {
                $result[] = [
                    'enum' => Having::class,
                    'condition' => Condition::DISJUNCTION(),
                    'data' => [$value],
                    'self' => self::$self
                ];
            }
            /** @var static */
            return Clause::having($result);
        } else {
            /** @var static */
            return Clause::having([
                'enum' => Having::class,
                'condition' => Condition::DISJUNCTION(),
                'data' => $data,
                'self' => self::$self
            ]);
        }
    }

    /**
     * @param array|string ...$data
     * @return static
     */
    public static function group(array|string ...$data): static
    {
        /** @var static */
        return Clause::group(['sorting' => Grouping::DEFAULT(), 'data' => $data, 'self' => self::$self]);
    }

    /**
     * @param array|string ...$data
     * @return static
     */
    public static function order(array|string ...$data): static
    {
        /** @var static */
        return Clause::order(['sorting' => Sorting::NONE(), 'data' => $data, 'self' => self::$self]);
    }

    /**
     * @param array|string ...$data
     * @return static
     */
    public static function orderAsc(array|string ...$data): static
    {
        /** @var static */
        return Clause::order(['sorting' => Sorting::ASCENDING(), 'data' => $data, 'self' => self::$self]);
    }

    /**
     * @param array|string ...$data
     * @return static
     */
    public static function orderDesc(array|string ...$data): static
    {
        /** @var static */
        return Clause::order(['sorting' => Sorting::DESCENDING(), 'data' => $data, 'self' => self::$self]);
    }

    /**
     * @param array|string ...$data
     * @return static
     */
    public static function limit(array|string ...$data): static
    {
        /** @var static */
        return Clause::limit(['data' => $data, 'self' => self::$self]);
    }

    /**
     * @throws Exceptions
     */
    private function runOnce(): void
    {
        $currentQuery = $this->parse();

        if (self::$lastQuery !== $currentQuery || self::$cursorExhausted) {
            $this->getContext()->query($currentQuery);
            self::$lastQuery = $currentQuery;
            self::$cursorExhausted = false;
        }
    }

    /**
     * @return void
     */
    public function reset(): void
    {
        self::$lastQuery = null;
        self::$cursorExhausted = true;
    }

    /**
     * @return string
     * @throws Exceptions
     */
    private function parse(): string
    {
        $buildRawResult = $this->buildRaw();
        $builder = new Builder($this->query);
        return $builder->parse(
            $buildRawResult,
            SQL::SQL_DIALECT_NONE,
            SQL::SQL_DIALECT_SINGLE_QUOTE
        );
    }

    /**
     * @throws Exceptions
     * @return string
     */
    public function build(): string
    {
        return (new Builder($this->query))->build();
    }

    /**
     * @throws Exceptions
     * @return string
     */
    public function buildRaw(): string
    {
        return (new Builder($this->query))->buildRaw();
    }

    /**
     * @return array
     */
    public function getValues(): array
    {
        return (new Builder($this->query))->getValues();
    }

    /**
     * @return object
     * @throws Exceptions
     */
    public function getAllMetadata(): object
    {
        $this->runOnce();
        return $this->getContext()->getAllMetadata();
    }

    /**
     * @param int|null $fetchStyle
     * @param mixed|null $fetchArgument
     * @param mixed|null $optArgs
     * @return mixed
     * @throws Exceptions
     */
    public function fetch(?int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): mixed
    {
        $this->runOnce();
        $result = $this->getContext()->fetch($fetchStyle, $fetchArgument, $optArgs);

        if ($result === false || $result === null) {
            self::$cursorExhausted = true;
        }

        return $result;
    }

    /**
     * @param int|null $fetchStyle
     * @param mixed|null $fetchArgument
     * @param mixed|null $optArgs
     * @return array|bool
     * @throws Exceptions
     */
    public function fetchAll(?int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): array|bool
    {
        $this->runOnce();
        $result = $this->getContext()->fetchAll($fetchStyle, $fetchArgument, $optArgs);

        self::$cursorExhausted = true;
        return $result;
    }
}
