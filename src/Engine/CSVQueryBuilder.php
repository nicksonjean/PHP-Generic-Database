<?php

declare(strict_types=1);

namespace GenericDatabase\Engine;

use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Interfaces\IQueryBuilder;
use GenericDatabase\Core\Where;
use GenericDatabase\Core\Having;
use GenericDatabase\Core\Select;
use GenericDatabase\Core\Sorting;
use GenericDatabase\Core\Grouping;
use GenericDatabase\Core\Condition;
use GenericDatabase\Helpers\Types\Compounds\Arrays;
use GenericDatabase\Shared\Singleton;
use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Generic\QueryBuilder\Query;
use GenericDatabase\Generic\QueryBuilder\Context;
use GenericDatabase\Engine\CSV\QueryBuilder\Builder;
use GenericDatabase\Engine\CSV\QueryBuilder\Clause;

/**
 * The `CSVQueryBuilder` class implements the `IQueryBuilder` interface to provide a flexible
 * query building mechanism for CSV flat file databases.
 *
 * @package GenericDatabase\Engine
 */
class CSVQueryBuilder implements IQueryBuilder
{
    use Query;
    use Context;
    use Singleton;

    private static CSVQueryBuilder $self;
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

    public static function join(array|string ...$data): static { return self::$self; }
    public static function selfJoin(array|string ...$data): static { return self::$self; }
    public static function leftJoin(array|string ...$data): static { return self::$self; }
    public static function rightJoin(array|string ...$data): static { return self::$self; }
    public static function innerJoin(array|string ...$data): static { return self::$self; }
    public static function outerJoin(array|string ...$data): static { return self::$self; }
    public static function crossJoin(array|string ...$data): static { return self::$self; }
    public static function on(array|string ...$data): static { return self::$self; }
    public static function andOn(array|string ...$data): static { return self::$self; }
    public static function orOn(array|string ...$data): static { return self::$self; }

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
        $currentQuery = $this->buildRaw();
        if (self::$lastQuery !== $currentQuery || self::$cursorExhausted) {
            $builder = new Builder($this->query);
            $data = $this->getContext()->getData();
            self::$cachedResult = $builder->execute($data);
            self::$lastQuery = $currentQuery;
            self::$cursorExhausted = false;
        }
    }

    public function reset(): void
    {
        self::$lastQuery = null;
        self::$cursorExhausted = true;
        self::$cachedResult = null;
    }

    public function build(): string { return (new Builder($this->query))->build(); }
    public function buildRaw(): string { return (new Builder($this->query))->buildRaw(); }
    public function getValues(): array { return (new Builder($this->query))->getValues(); }

    public function getAllMetadata(): object
    {
        $this->runOnce();
        return (object) ['queryRows' => count(self::$cachedResult ?? []), 'affectedRows' => 0];
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
        return $result;
    }

    public function fetchAll(?int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): array|bool
    {
        $this->runOnce();
        $result = self::$cachedResult ?? [];
        self::$cursorExhausted = true;
        self::$cachedResult = null;
        return $result;
    }
}


