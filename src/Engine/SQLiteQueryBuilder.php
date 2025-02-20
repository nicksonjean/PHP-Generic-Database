<?php

namespace GenericDatabase\Engine;

use stdClass;
use ReflectionException;
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
use GenericDatabase\Engine\SQLite\QueryBuilder\Context;
use GenericDatabase\Engine\SQLite\QueryBuilder\Query;
use GenericDatabase\Engine\SQLite\QueryBuilder\Builder;
use GenericDatabase\Engine\SQLite\QueryBuilder\Internal;

class SQLiteQueryBuilder implements IQueryBuilder
{
    use Query;
    use Context;
    use Singleton;

    private static $self;

    private static ?string $lastQuery = null;

    private static bool $cursorExhausted = false;

    public function __construct(IConnection $context = null)
    {
        $this->query = new stdClass();
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
        return Internal::select(['type' => Select::DEFAULT, 'data' => $data, 'self' => self::$self]);
    }

    /**
     * @param array|string ...$data
     * @return static
     */
    public static function distinct(array|string ...$data): static
    {
        /** @var static */
        return Internal::select(['type' => Select::DISTINCT, 'data' => $data, 'self' => self::$self]);
    }

    /**
     * @param array|string ...$data
     * @return static
     */
    public static function from(array|string ...$data): static
    {
        /** @var static */
        return Internal::from(['data' => $data, 'self' => self::$self]);
    }

    /**
     * @param array|string ...$data
     * @return static
     */
    public static function join(array|string ...$data): static
    {
        /** @var static */
        return Internal::join(
            ['type' => Join::DEFAULT, 'junction' => Junction::NONE, 'data' => $data, 'self' => self::$self]
        );
    }

    /**
     * @param array|string ...$data
     * @return static
     */
    public static function selfJoin(array|string ...$data): static
    {
        /** @var static */
        return Internal::join(
            ['type' => Join::SELF, 'junction' => Junction::NONE, 'data' => $data, 'self' => self::$self]
        );
    }

    /**
     * @param array|string ...$data
     * @return static
     */
    public static function leftJoin(array|string ...$data): static
    {
        /** @var static */
        return Internal::join(
            ['type' => Join::LEFT, 'junction' => Junction::NONE, 'data' => $data, 'self' => self::$self]
        );
    }

    /**
     * @param array|string ...$data
     * @return static
     */
    public static function rightJoin(array|string ...$data): static
    {
        /** @var static */
        return Internal::join(
            ['type' => Join::RIGHT, 'junction' => Junction::NONE, 'data' => $data, 'self' => self::$self]
        );
    }

    /**
     * @param array|string ...$data
     * @return static
     */
    public static function innerJoin(array|string ...$data): static
    {
        /** @var static */
        return Internal::join(
            ['type' => Join::INNER, 'junction' => Junction::NONE, 'data' => $data, 'self' => self::$self]
        );
    }

    /**
     * @param array|string ...$data
     * @return static
     */
    public static function outerJoin(array|string ...$data): static
    {
        /** @var static */
        return Internal::join(
            ['type' => Join::OUTER, 'junction' => Junction::NONE, 'data' => $data, 'self' => self::$self]
        );
    }

    /**
     * @param array|string ...$data
     * @return static
     */
    public static function crossJoin(array|string ...$data): static
    {
        /** @var static */
        return Internal::join(
            ['type' => Join::CROSS, 'junction' => Junction::NONE, 'data' => $data, 'self' => self::$self]
        );
    }

    /**
     * @param array|string ...$data
     * @return static
     */
    public static function on(array|string ...$data): static
    {
        /** @var static */
        return Internal::on(['junction' => Junction::NONE, 'data' => $data, 'self' => self::$self]);
    }

    /**
     * @param array|string ...$data
     * @return static
     */
    public static function andOn(array|string ...$data): static
    {
        /** @var static */
        return Internal::on(['junction' => Junction::CONJUNCTION, 'data' => $data, 'self' => self::$self]);
    }

    /**
     * @param array|string ...$data
     * @return static
     */
    public static function orOn(array|string ...$data): static
    {
        /** @var static */
        return Internal::on(['junction' => Junction::DISJUNCTION, 'data' => $data, 'self' => self::$self]);
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
                $condition = Condition::NONE;
                $keys = array_keys($value)[0];
                $values = array_values($value);
                if (is_string($keys)) {
                    $condition = $keys === 'AND' ? Condition::CONJUNCTION : Condition::DISJUNCTION;
                }
                $result[] = [
                    'enum' => Where::class,
                    'condition' => $condition,
                    'data' => [$values],
                    'self' => self::$self
                ];
            }
            /** @var static */
            return Internal::where($result);
        } else {
            /** @var static */
            return Internal::where([
                'enum' => Where::class,
                'condition' => Condition::NONE,
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
                $condition = Condition::CONJUNCTION;
                $keys = array_keys($value)[0];
                $values = array_values($value);
                if (is_string($keys)) {
                    $condition = $keys === 'AND' ? Condition::CONJUNCTION : Condition::DISJUNCTION;
                }
                $result[] = [
                    'enum' => Where::class,
                    'condition' => $condition,
                    'data' => [$values],
                    'self' => self::$self
                ];
            }
            /** @var static */
            return Internal::where($result);
        } else {
            /** @var static */
            return Internal::where([
                'enum' => Where::class,
                'condition' => Condition::CONJUNCTION,
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
                $condition = Condition::DISJUNCTION;
                $keys = array_keys($value)[0];
                $values = array_values($value);
                if (is_string($keys)) {
                    $condition = $keys === 'AND' ? Condition::CONJUNCTION : Condition::DISJUNCTION;
                }
                $result[] = [
                    'enum' => Where::class,
                    'condition' => $condition,
                    'data' => [$values],
                    'self' => self::$self
                ];
            }
            /** @var static */
            return Internal::where($result);
        } else {
            /** @var static */
            return Internal::where([
                'enum' => Where::class,
                'condition' => Condition::DISJUNCTION,
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
                $condition = Condition::NONE;
                $keys = array_keys($value)[0];
                $values = array_values($value);
                if (is_string($keys)) {
                    $condition = $keys === 'AND' ? Condition::CONJUNCTION : Condition::DISJUNCTION;
                }
                $result[] = [
                    'enum' => Having::class,
                    'condition' => $condition,
                    'data' => [$values],
                    'self' => self::$self
                ];
            }
            /** @var static */
            return Internal::having($result);
        } else {
            /** @var static */
            return Internal::having([
                'enum' => Having::class,
                'condition' => Condition::NONE,
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
                    'condition' => Condition::CONJUNCTION,
                    'data' => [$value],
                    'self' => self::$self
                ];
            }
            /** @var static */
            return Internal::having($result);
        } else {
            /** @var static */
            return Internal::having([
                'enum' => Having::class,
                'condition' => Condition::CONJUNCTION,
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
                    'condition' => Condition::DISJUNCTION,
                    'data' => [$value],
                    'self' => self::$self
                ];
            }
            /** @var static */
            return Internal::having($result);
        } else {
            /** @var static */
            return Internal::having([
                'enum' => Having::class,
                'condition' => Condition::DISJUNCTION,
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
        return Internal::group(['sorting' => Grouping::DEFAULT, 'data' => $data, 'self' => self::$self]);
    }

    /**
     * @param array|string ...$data
     * @return static
     */
    public static function order(array|string ...$data): static
    {
        /** @var static */
        return Internal::order(['sorting' => Sorting::NONE, 'data' => $data, 'self' => self::$self]);
    }

    /**
     * @param array|string ...$data
     * @return static
     */
    public static function orderAsc(array|string ...$data): static
    {
        /** @var static */
        return Internal::order(['sorting' => Sorting::ASCENDING, 'data' => $data, 'self' => self::$self]);
    }

    /**
     * @param array|string ...$data
     * @return static
     */
    public static function orderDesc(array|string ...$data): static
    {
        /** @var static */
        return Internal::order(['sorting' => Sorting::DESCENDING, 'data' => $data, 'self' => self::$self]);
    }

    /**
     * @param array|string ...$data
     * @return static
     */
    public static function limit(array|string ...$data): static
    {
        /** @var static */
        return Internal::limit(['data' => $data, 'self' => self::$self]);
    }

    /**
     * @throws ReflectionException
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
     * @throws ReflectionException
     */
    public function reset(): void
    {
        self::$lastQuery = null;
        self::$cursorExhausted = true;
    }

    /**
     * @throws ReflectionException
     * @return string
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
     * @throws Exceptions
     * @return array
     */
    public function getValues(): array
    {
        return (new Builder($this->query))->getValues();
    }

    /**
     * @throws Exceptions
     * @return object
     */
    public function getAllMetadata(): object
    {
        $this->runOnce();
        return $this->getContext()->getAllMetadata();
    }

    /**
     * @throws ReflectionException
     * @return mixed
     */
    public function fetch(int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): mixed
    {
        $this->runOnce();
        $result = $this->getContext()->fetch($fetchStyle, $fetchArgument, $optArgs);

        if ($result === false || $result === null) {
            self::$cursorExhausted = true;
        }

        return $result;
    }

    /**
     * @throws ReflectionException
     * @return array|bool
     */
    public function fetchAll(int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): array|bool
    {
        $this->runOnce();
        $result = $this->getContext()->fetchAll($fetchStyle, $fetchArgument, $optArgs);

        self::$cursorExhausted = true;
        return $result;
    }
}
