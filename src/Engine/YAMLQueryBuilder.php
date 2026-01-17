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
use GenericDatabase\Engine\JSON\QueryBuilder\Builder;
use GenericDatabase\Engine\JSON\QueryBuilder\Criteria;

/**
 * The `YAMLQueryBuilder` class implements the `IQueryBuilder` interface to provide a flexible
 * query building mechanism for YAML flat file databases.
 *
 * @package GenericDatabase\Engine
 */
class YAMLQueryBuilder implements IQueryBuilder
{
    use Query;
    use Context;
    use Singleton;

    private static YAMLQueryBuilder $self;
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

    private static function buildSelect(array $arguments): IQueryBuilder
    {
        $type = $arguments['type'] ?? Select::DEFAULT();
        $data = $arguments['data'] ?? [];
        self::$self->query->select['type'] = $type;
        foreach ($data as $column) {
            if (is_array($column)) {
                array_map(fn($key) => self::$self->query->select['columns'][] = Criteria::getSelect(['data' => $key]), $column);
            } elseif (is_string($column)) {
                if (str_contains($column, ',')) {
                    array_map(fn($key) => self::$self->query->select['columns'][] = Criteria::getSelect(['data' => $key]), explode(',', $column));
                } else {
                    self::$self->query->select['columns'][] = Criteria::getSelect(['data' => $column]);
                }
            }
        }
        return self::$self;
    }

    public static function select(array|string ...$data): static
    {
        return self::buildSelect(['type' => Select::DEFAULT(), 'data' => $data]);
    }

    public static function distinct(array|string ...$data): static
    {
        return self::buildSelect(['type' => Select::DISTINCT(), 'data' => $data]);
    }

    public static function from(array|string ...$data): static
    {
        foreach ($data as $table) {
            if (is_string($table)) {
                if (str_contains($table, ',')) {
                    foreach (explode(',', $table) as $t) {
                        self::$self->query->from[] = Criteria::getFrom(['data' => trim($t)]);
                    }
                } else {
                    self::$self->query->from[] = Criteria::getFrom(['data' => $table]);
                }
            }
        }
        return self::$self;
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

    private static function buildWhere(array $data, $condition): IQueryBuilder
    {
        foreach ($data as $column) {
            if (is_string($column)) {
                self::$self->query->where[] = Criteria::getWhereHaving(['data' => $column, 'enum' => Where::class, 'condition' => $condition]);
            }
        }
        return self::$self;
    }

    public static function where(array|string ...$data): static { return self::buildWhere($data, Condition::NONE()); }
    public static function andWhere(array|string ...$data): static { return self::buildWhere($data, Condition::CONJUNCTION()); }
    public static function orWhere(array|string ...$data): static { return self::buildWhere($data, Condition::DISJUNCTION()); }

    private static function buildHaving(array $data, $condition): IQueryBuilder
    {
        foreach ($data as $column) {
            if (is_string($column)) {
                self::$self->query->having[] = Criteria::getWhereHaving(['data' => $column, 'enum' => Having::class, 'condition' => $condition]);
            }
        }
        return self::$self;
    }

    public static function having(array|string ...$data): static { return self::buildHaving($data, Condition::NONE()); }
    public static function andHaving(array|string ...$data): static { return self::buildHaving($data, Condition::CONJUNCTION()); }
    public static function orHaving(array|string ...$data): static { return self::buildHaving($data, Condition::DISJUNCTION()); }

    public static function group(array|string ...$data): static
    {
        foreach ($data as $column) {
            if (is_string($column)) {
                if (str_contains($column, ',')) {
                    foreach (explode(',', $column) as $c) {
                        self::$self->query->group[] = Criteria::getGroup(['data' => trim($c)]);
                    }
                } else {
                    self::$self->query->group[] = Criteria::getGroup(['data' => $column]);
                }
            }
        }
        return self::$self;
    }

    private static function buildOrder(array $data, $sorting): IQueryBuilder
    {
        foreach ($data as $column) {
            if (is_string($column)) {
                if (str_contains($column, ',')) {
                    foreach (explode(',', $column) as $c) {
                        self::$self->query->order[] = Criteria::getOrder(['sorting' => $sorting, 'data' => trim($c)]);
                    }
                } else {
                    self::$self->query->order[] = Criteria::getOrder(['sorting' => $sorting, 'data' => $column]);
                }
            }
        }
        return self::$self;
    }

    public static function order(array|string ...$data): static { return self::buildOrder($data, Sorting::NONE()); }
    public static function orderAsc(array|string ...$data): static { return self::buildOrder($data, Sorting::ASCENDING()); }
    public static function orderDesc(array|string ...$data): static { return self::buildOrder($data, Sorting::DESCENDING()); }

    public static function limit(array|string ...$data): static
    {
        if (count($data) > 1) {
            $data = [implode(', ', $data)];
        }
        self::$self->query->limit = Criteria::getLimit(['data' => reset($data)]);
        return self::$self;
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
