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
use GenericDatabase\Engine\XML\Connection\XML;
use GenericDatabase\Engine\XML\QueryBuilder\Builder;
use GenericDatabase\Engine\XML\QueryBuilder\Clause;

/**
 * The `XMLQueryBuilder` class implements the `IQueryBuilder` interface to provide a flexible
 * query building mechanism for XML flat file databases.
 *
 * @package GenericDatabase\Engine
 */
class XMLQueryBuilder implements IQueryBuilder
{
    use Query;
    use Context;
    use Singleton;

    private static XMLQueryBuilder $self;
    private static ?string $lastQuery = null;
    private static bool $cursorExhausted = false;
    private static ?array $cachedResult = null;

    public function __construct(?IConnection $context = null)
    {
        $this->initQuery();
        self::$context = $context;
        self::$self = $this;
    }

    public static function with(IConnection $context): self
    {
        self::$context = $context;
        self::$self = new static($context);
        return self::$self;
    }

    public static function select(array|string ...$data): static
    {
        return Clause::select(['type' => Select::DEFAULT(), 'data' => $data, 'self' => self::$self]);
    }

    public static function distinct(array|string ...$data): static
    {
        return Clause::select(['type' => Select::DISTINCT(), 'data' => $data, 'self' => self::$self]);
    }

    public static function from(array|string ...$data): static
    {
        return Clause::from(['data' => $data, 'self' => self::$self]);
    }

    public static function join(array|string ...$data): static
    {
        return Clause::join(
            ['type' => Join::DEFAULT(), 'junction' => Junction::NONE(), 'data' => $data, 'self' => self::$self]
        );
    }

    public static function selfJoin(array|string ...$data): static
    {
        return Clause::join(
            ['type' => Join::SELF(), 'junction' => Junction::NONE(), 'data' => $data, 'self' => self::$self]
        );
    }

    public static function leftJoin(array|string ...$data): static
    {
        return Clause::join(
            ['type' => Join::LEFT(), 'junction' => Junction::NONE(), 'data' => $data, 'self' => self::$self]
        );
    }

    public static function rightJoin(array|string ...$data): static
    {
        return Clause::join(
            ['type' => Join::RIGHT(), 'junction' => Junction::NONE(), 'data' => $data, 'self' => self::$self]
        );
    }

    public static function innerJoin(array|string ...$data): static
    {
        return Clause::join(
            ['type' => Join::INNER(), 'junction' => Junction::NONE(), 'data' => $data, 'self' => self::$self]
        );
    }

    public static function outerJoin(array|string ...$data): static
    {
        return Clause::join(
            ['type' => Join::OUTER(), 'junction' => Junction::NONE(), 'data' => $data, 'self' => self::$self]
        );
    }

    public static function crossJoin(array|string ...$data): static
    {
        return Clause::join(
            ['type' => Join::CROSS(), 'junction' => Junction::NONE(), 'data' => $data, 'self' => self::$self]
        );
    }

    public static function on(array|string ...$data): static
    {
        return Clause::on(['junction' => Junction::NONE(), 'data' => $data, 'self' => self::$self]);
    }

    public static function andOn(array|string ...$data): static
    {
        return Clause::on(['junction' => Junction::CONJUNCTION(), 'data' => $data, 'self' => self::$self]);
    }

    public static function orOn(array|string ...$data): static
    {
        return Clause::on(['junction' => Junction::DISJUNCTION(), 'data' => $data, 'self' => self::$self]);
    }

    public static function where(array|string ...$data): static
    {
        if (Arrays::isDepthArray($data) > 2) {
            $result = [];
            foreach (reset($data) as $value) {
                $condition = Condition::NONE();
                $keys = array_keys($value)[0];
                if (is_string($keys)) {
                    $condition = $keys === 'AND' ? Condition::CONJUNCTION() : Condition::DISJUNCTION();
                }
                $result[] = ['enum' => Where::class, 'condition' => $condition, 'data' => [array_values($value)], 'self' => self::$self];
            }
            return Clause::where($result);
        }
        return Clause::where(['enum' => Where::class, 'condition' => Condition::NONE(), 'data' => $data, 'self' => self::$self]);
    }

    public static function andWhere(array|string ...$data): static
    {
        if (Arrays::isDepthArray($data) > 2) {
            $result = [];
            foreach (reset($data) as $value) {
                $condition = Condition::CONJUNCTION();
                $keys = array_keys($value)[0];
                if (is_string($keys)) {
                    $condition = $keys === 'AND' ? Condition::CONJUNCTION() : Condition::DISJUNCTION();
                }
                $result[] = ['enum' => Where::class, 'condition' => $condition, 'data' => [array_values($value)], 'self' => self::$self];
            }
            return Clause::where($result);
        }
        return Clause::where(['enum' => Where::class, 'condition' => Condition::CONJUNCTION(), 'data' => $data, 'self' => self::$self]);
    }

    public static function orWhere(array|string ...$data): static
    {
        if (Arrays::isDepthArray($data) > 2) {
            $result = [];
            foreach (reset($data) as $value) {
                $condition = Condition::DISJUNCTION();
                $keys = array_keys($value)[0];
                if (is_string($keys)) {
                    $condition = $keys === 'AND' ? Condition::CONJUNCTION() : Condition::DISJUNCTION();
                }
                $result[] = ['enum' => Where::class, 'condition' => $condition, 'data' => [array_values($value)], 'self' => self::$self];
            }
            return Clause::where($result);
        }
        return Clause::where(['enum' => Where::class, 'condition' => Condition::DISJUNCTION(), 'data' => $data, 'self' => self::$self]);
    }

    public static function having(array|string ...$data): static
    {
        return Clause::having(['enum' => Having::class, 'condition' => Condition::NONE(), 'data' => $data, 'self' => self::$self]);
    }

    public static function andHaving(array|string ...$data): static
    {
        return Clause::having(['enum' => Having::class, 'condition' => Condition::CONJUNCTION(), 'data' => $data, 'self' => self::$self]);
    }

    public static function orHaving(array|string ...$data): static
    {
        return Clause::having(['enum' => Having::class, 'condition' => Condition::DISJUNCTION(), 'data' => $data, 'self' => self::$self]);
    }

    public static function group(array|string ...$data): static
    {
        return Clause::group(['sorting' => Grouping::DEFAULT(), 'data' => $data, 'self' => self::$self]);
    }

    public static function order(array|string ...$data): static
    {
        return Clause::order(['sorting' => Sorting::NONE(), 'data' => $data, 'self' => self::$self]);
    }

    public static function orderAsc(array|string ...$data): static
    {
        return Clause::order(['sorting' => Sorting::ASCENDING(), 'data' => $data, 'self' => self::$self]);
    }

    public static function orderDesc(array|string ...$data): static
    {
        return Clause::order(['sorting' => Sorting::DESCENDING(), 'data' => $data, 'self' => self::$self]);
    }

    public static function limit(array|string ...$data): static
    {
        return Clause::limit(['data' => $data, 'self' => self::$self]);
    }

    private function runOnce(): void
    {
        $currentQueryForDisplay = $this->buildRaw();
        $currentQueryForExecution = (new Builder($this->query))->buildRawForExecution();

        if (self::$lastQuery !== $currentQueryForDisplay || self::$cursorExhausted) {
            $conn = $this->getContext();
            $conn->query($currentQueryForExecution);
            $rows = $conn->fetchAll(XML::FETCH_ASSOC);
            self::$cachedResult = is_array($rows) ? $rows : [];
            $conn->setQueryString($currentQueryForDisplay);
            self::$lastQuery = $currentQueryForDisplay;
            self::$cursorExhausted = false;
        }
    }

    public function reset(): void
    {
        self::$lastQuery = null;
        self::$cursorExhausted = true;
        self::$cachedResult = null;
    }

    public function build(): string
    {
        return (new Builder($this->query))->build();
    }
    public function buildRaw(): string
    {
        return (new Builder($this->query))->buildRaw();
    }
    public function getValues(): array
    {
        return (new Builder($this->query))->getValues();
    }

    public function getAllMetadata(): object
    {
        $this->runOnce();
        $result = self::$cachedResult ?? [];
        $rowCount = count($result);
        $colCount = $rowCount > 0 ? count((array) reset($result)) : 0;

        $metadata = new Metadata();
        $metadata->query->setString($this->getContext()->getQueryString());
        $metadata->query->setArguments([]);
        $metadata->query->setColumns($colCount);
        $metadata->query->getRows()->setFetched($rowCount);
        $aff = $this->getContext()->getAffectedRows();
        $metadata->query->getRows()->setAffected(is_int($aff) ? $aff : 0);

        return $metadata;
    }

    private function formatRow(mixed $row, int $fetchStyle, mixed $fetchArgument = null, mixed $optArgs = null): mixed
    {
        $row = (array) $row;

        return match ($fetchStyle) {
            XML::FETCH_NUM => array_values($row),
            XML::FETCH_BOTH => $this->formatBothMode($row),
            XML::FETCH_OBJ => (object) $row,
            XML::FETCH_COLUMN => $fetchArgument !== null
                ? ($row[$fetchArgument] ?? array_values($row)[0] ?? null)
                : (array_values($row)[0] ?? null),
            XML::FETCH_CLASS => $this->fetchClass($row, $fetchArgument, $optArgs),
            XML::FETCH_INTO => $this->fetchInto($row, $fetchArgument),
            default => $row,
        };
    }

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
        if ($fetchStyle !== null) {
            return $this->formatRow($result, $fetchStyle, $fetchArgument, $optArgs);
        }
        return $result;
    }

    public function fetchAll(?int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): array|bool
    {
        $this->runOnce();
        $result = self::$cachedResult ?? [];
        self::$cursorExhausted = true;
        self::$cachedResult = null;
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
