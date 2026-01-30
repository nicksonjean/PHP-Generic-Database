<?php

declare(strict_types=1);

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
use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Generic\QueryBuilder\Query;
use GenericDatabase\Generic\QueryBuilder\Context;
use GenericDatabase\Generic\Statements\Metadata;
use GenericDatabase\Engine\JSON\QueryBuilder\Builder;
use GenericDatabase\Engine\JSON\QueryBuilder\Clause;
use GenericDatabase\Engine\JSON\Connection\JSON;

/**
 * The `JSONQueryBuilder` class implements the `IQueryBuilder` interface to provide a flexible
 * query building mechanism for JSON flat file databases.
 *
 * Example Usage:
 * <code>
 * $connection = new JSONConnection();
 * $connection->setFilePath('/path/to/data.json');
 * $connection->connect();
 *
 * $builder = JSONQueryBuilder::with($connection);
 * $results = $builder
 *     ->select('name', 'age')
 *     ->from('users')
 *     ->where('age > 18')
 *     ->orderBy('name')
 *     ->fetchAll();
 * </code>
 *
 * @package GenericDatabase\Engine
 */
class JSONQueryBuilder implements IQueryBuilder
{
    use Query;
    use Context;
    use Singleton;

    /**
     * @var JSONQueryBuilder The singleton instance.
     */
    private static JSONQueryBuilder $self;

    /**
     * @var string|null The last executed query.
     */
    private static ?string $lastQuery = null;

    /**
     * @var bool Whether the cursor is exhausted.
     */
    private static bool $cursorExhausted = false;

    /**
     * @var array|null Cached result set.
     */
    private static ?array $cachedResult = null;

    /**
     * Constructor.
     *
     * @param IConnection|null $context The connection context.
     */
    public function __construct(?IConnection $context = null)
    {
        $this->initQuery();
        self::$context = $context;
        self::$self = $this;
    }

    /**
     * Static initializer with context.
     *
     * @param IConnection $context The connection.
     * @return self
     */
    public static function with(IConnection $context): self
    {
        self::$context = $context;
        self::$self = new static($context);
        return self::$self;
    }

    /**
     * Add SELECT clause.
     *
     * @param array|string ...$data The columns to select.
     * @return static
     */
    public static function select(array|string ...$data): static
    {
        /** @var static */
        return Clause::select(['type' => Select::DEFAULT(), 'data' => $data, 'self' => self::$self]);
    }

    /**
     * Add DISTINCT clause.
     *
     * @param array|string ...$data The columns to select distinctly.
     * @return static
     */
    public static function distinct(array|string ...$data): static
    {
        /** @var static */
        return Clause::select(['type' => Select::DISTINCT(), 'data' => $data, 'self' => self::$self]);
    }

    /**
     * Add FROM clause.
     *
     * @param array|string ...$data The tables/files to select from.
     * @return static
     */
    public static function from(array|string ...$data): static
    {
        /** @var static */
        return Clause::from(['data' => $data, 'self' => self::$self]);
    }

    /**
     * Add JOIN clause.
     *
     * @param array|string ...$data The join parameters.
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
     * Add SELF JOIN clause.
     *
     * @param array|string ...$data The join parameters.
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
     * Add LEFT JOIN clause.
     *
     * @param array|string ...$data The join parameters.
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
     * Add RIGHT JOIN clause.
     *
     * @param array|string ...$data The join parameters.
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
     * Add INNER JOIN clause.
     *
     * @param array|string ...$data The join parameters.
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
     * Add OUTER JOIN clause.
     *
     * @param array|string ...$data The join parameters.
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
     * Add CROSS JOIN clause.
     *
     * @param array|string ...$data The join parameters.
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
     * Add ON clause.
     *
     * @param array|string ...$data The on parameters.
     * @return static
     */
    public static function on(array|string ...$data): static
    {
        /** @var static */
        return Clause::on(['junction' => Junction::NONE(), 'data' => $data, 'self' => self::$self]);
    }

    /**
     * Add AND ON clause.
     *
     * @param array|string ...$data The on parameters.
     * @return static
     */
    public static function andOn(array|string ...$data): static
    {
        /** @var static */
        return Clause::on(['junction' => Junction::CONJUNCTION(), 'data' => $data, 'self' => self::$self]);
    }

    /**
     * Add OR ON clause.
     *
     * @param array|string ...$data The on parameters.
     * @return static
     */
    public static function orOn(array|string ...$data): static
    {
        /** @var static */
        return Clause::on(['junction' => Junction::DISJUNCTION(), 'data' => $data, 'self' => self::$self]);
    }

    /**
     * Add WHERE clause.
     *
     * @param array|string ...$data The where conditions.
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
     * Add AND WHERE clause.
     *
     * @param array|string ...$data The where conditions.
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
     * Add OR WHERE clause.
     *
     * @param array|string ...$data The where conditions.
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
     * Add HAVING clause.
     *
     * @param array|string ...$data The having conditions.
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
     * Add AND HAVING clause.
     *
     * @param array|string ...$data The having conditions.
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
     * Add OR HAVING clause.
     *
     * @param array|string ...$data The having conditions.
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
     * Add GROUP BY clause.
     *
     * @param array|string ...$data The columns to group by.
     * @return static
     */
    public static function group(array|string ...$data): static
    {
        /** @var static */
        return Clause::group(['sorting' => Grouping::DEFAULT(), 'data' => $data, 'self' => self::$self]);
    }

    /**
     * Add ORDER BY clause.
     *
     * @param array|string ...$data The columns to order by.
     * @return static
     */
    public static function order(array|string ...$data): static
    {
        /** @var static */
        return Clause::order(['sorting' => Sorting::NONE(), 'data' => $data, 'self' => self::$self]);
    }

    /**
     * Add ORDER BY ASC clause.
     *
     * @param array|string ...$data The columns to order by.
     * @return static
     */
    public static function orderAsc(array|string ...$data): static
    {
        /** @var static */
        return Clause::order(['sorting' => Sorting::ASCENDING(), 'data' => $data, 'self' => self::$self]);
    }

    /**
     * Add ORDER BY DESC clause.
     *
     * @param array|string ...$data The columns to order by.
     * @return static
     */
    public static function orderDesc(array|string ...$data): static
    {
        /** @var static */
        return Clause::order(['sorting' => Sorting::DESCENDING(), 'data' => $data, 'self' => self::$self]);
    }

    /**
     * Add LIMIT clause.
     *
     * @param array|string ...$data The limit parameters.
     * @return static
     */
    public static function limit(array|string ...$data): static
    {
        /** @var static */
        return Clause::limit(['data' => $data, 'self' => self::$self]);
    }

    /**
     * Execute the query and cache results.
     * Passes unquoted query (buildRawForExecution) to execution; sets metadata
     * query string to display version (buildRaw with double quotes) after fetch.
     *
     * @return void
     * @throws Exceptions
     */
    private function runOnce(): void
    {
        $currentQueryForDisplay = $this->buildRaw();
        $currentQueryForExecution = (new Builder($this->query))->buildRawForExecution();

        if (self::$lastQuery !== $currentQueryForDisplay || self::$cursorExhausted) {
            $conn = $this->getContext();
            $conn->query($currentQueryForExecution);
            $rows = $conn->fetchAll(JSON::FETCH_ASSOC);
            self::$cachedResult = is_array($rows) ? $rows : [];
            $conn->setQueryString($currentQueryForDisplay);
            self::$lastQuery = $currentQueryForDisplay;
            self::$cursorExhausted = false;
        }
    }

    /**
     * Reset the query state.
     *
     * @return void
     */
    public function reset(): void
    {
        self::$lastQuery = null;
        self::$cursorExhausted = true;
        self::$cachedResult = null;
    }

    /**
     * Build the query string.
     *
     * @return string
     * @throws Exceptions
     */
    public function build(): string
    {
        return (new Builder($this->query))->build();
    }

    /**
     * Build the raw query string with values.
     *
     * @return string
     * @throws Exceptions
     */
    public function buildRaw(): string
    {
        return (new Builder($this->query))->buildRaw();
    }

    /**
     * Get the query values.
     *
     * @return array
     */
    public function getValues(): array
    {
        return (new Builder($this->query))->getValues();
    }

    /**
     * Get all metadata.
     * Returns Metadata structure matching SQLiteQueryBuilder (QueryMetadata, RowsMetadata).
     *
     * @return Metadata
     * @throws Exceptions
     */
    public function getAllMetadata(): Metadata
    {
        $this->runOnce();
        $result = self::$cachedResult ?? [];
        $rowCount = \count($result);
        $colCount = $rowCount > 0 ? \count((array) reset($result)) : 0;

        $metadata = new Metadata();
        $metadata->query->setString($this->getContext()->getQueryString());
        $metadata->query->setArguments([]);
        $metadata->query->setColumns($colCount);
        $metadata->query->getRows()->setFetched($rowCount);
        $aff = $this->getContext()->getAffectedRows();
        $metadata->query->getRows()->setAffected(is_int($aff) ? $aff : 0);

        return $metadata;
    }

    /**
     * Format a row based on the fetch style.
     *
     * @param mixed $row The row to format.
     * @param int $fetchStyle The fetch style.
     * @param mixed|null $fetchArgument The fetch argument.
     * @param mixed|null $optArgs Additional options.
     * @return mixed The formatted row.
     */
    private function formatRow(mixed $row, int $fetchStyle, mixed $fetchArgument = null, mixed $optArgs = null): mixed
    {
        $row = (array) $row;

        return match ($fetchStyle) {
            JSON::FETCH_NUM => array_values($row),
            JSON::FETCH_BOTH => $this->formatBothMode($row),
            JSON::FETCH_OBJ => (object) $row,
            JSON::FETCH_COLUMN => $fetchArgument !== null
                ? ($row[$fetchArgument] ?? array_values($row)[0] ?? null)
                : (array_values($row)[0] ?? null),
            JSON::FETCH_CLASS => $this->fetchClass($row, $fetchArgument, $optArgs),
            JSON::FETCH_INTO => $this->fetchInto($row, $fetchArgument),
            default => $row, // FETCH_ASSOC
        };
    }

    /**
     * Format row for FETCH_BOTH mode with alternating indices.
     *
     * @param array $row The row data.
     * @return array The formatted row with alternating numeric and associative indices.
     */
    private function formatBothMode(array $row): array
    {
        $result = [];
        $index = 0;

        foreach ($row as $key => $value) {
            $result[$index] = $value;
            $result[$key] = $value;
            $index++;
        }

        return $result;
    }

    /**
     * Fetch row into a class instance.
     *
     * @param array $row The row data.
     * @param string|null $className The class name.
     * @param mixed $ctorArgs Constructor arguments.
     * @return object The class instance.
     */
    private function fetchClass(array $row, ?string $className, mixed $ctorArgs): object
    {
        if ($className === null) {
            return (object) $row;
        }

        $instance = $ctorArgs !== null
            ? new $className(...(array) $ctorArgs)
            : new $className();

        foreach ($row as $key => $value) {
            $instance->$key = $value;
        }

        return $instance;
    }

    /**
     * Fetch row into an existing object.
     *
     * @param array $row The row data.
     * @param object|null $object The object to populate.
     * @return object The populated object.
     */
    private function fetchInto(array $row, ?object $object): object
    {
        if ($object === null) {
            return (object) $row;
        }

        foreach ($row as $key => $value) {
            $object->$key = $value;
        }

        return $object;
    }

    /**
     * Fetch a single row.
     *
     * @param int|null $fetchStyle The fetch style.
     * @param mixed|null $fetchArgument The fetch argument.
     * @param mixed|null $optArgs Additional options.
     * @return mixed
     * @throws Exceptions
     */
    public function fetch(?int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): mixed
    {
        $this->runOnce();

        if (self::$cachedResult === null || empty(self::$cachedResult)) {
            self::$cursorExhausted = true;
            return false;
        }

        $result = array_shift(self::$cachedResult);

        if ($result === null) {
            self::$cursorExhausted = true;
            return false;
        }

        // Format the result using internal formatRow method
        if ($fetchStyle !== null) {
            return $this->formatRow($result, $fetchStyle, $fetchArgument, $optArgs);
        }

        return $result;
    }

    /**
     * Fetch all rows.
     *
     * @param int|null $fetchStyle The fetch style.
     * @param mixed|null $fetchArgument The fetch argument.
     * @param mixed|null $optArgs Additional options.
     * @return array|bool
     * @throws Exceptions
     */
    public function fetchAll(?int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): array|bool
    {
        $this->runOnce();

        $result = self::$cachedResult ?? [];
        self::$cursorExhausted = true;
        self::$cachedResult = null;

        // Format all results if fetch style is specified
        if ($fetchStyle !== null && !empty($result)) {
            $formatted = [];
            foreach ($result as $row) {
                $formatted[] = $this->formatRow($row, $fetchStyle, $fetchArgument, $optArgs);
            }
            return $formatted;
        }

        return $result;
    }
}
