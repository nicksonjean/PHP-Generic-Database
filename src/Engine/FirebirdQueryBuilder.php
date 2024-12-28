<?php

namespace GenericDatabase\Engine;

use stdClass;
use ReflectionMethod;
use ReflectionException;
use GenericDatabase\Core\Build;
use GenericDatabase\Core\Join;
use GenericDatabase\Core\Where;
use GenericDatabase\Core\Having;
use GenericDatabase\Core\Select;
use GenericDatabase\Core\Sorting;
use GenericDatabase\Core\Grouping;
use GenericDatabase\Core\Junction;
use GenericDatabase\IQueryBuilder;
use GenericDatabase\Core\Condition;
use GenericDatabase\Helpers\Arrays;
use GenericDatabase\Shared\Singleton;
use GenericDatabase\Helpers\Translater;
use GenericDatabase\Helpers\CustomException;
use GenericDatabase\Engine\Firebird\QueryBuilder\Query;
use GenericDatabase\Engine\Firebird\QueryBuilder\Builder;
use GenericDatabase\Engine\Firebird\QueryBuilder\Internal;

class FirebirdQueryBuilder implements IQueryBuilder
{
    use Query;
    use Singleton;

    private static Build $directive;

    private static bool $caller;

    public function __construct(?Build $build = null)
    {
        self::$directive = $build ?? Build::BEFORE;
        $this->query = new stdClass();
        $this->query->build = self::$directive;
        self::$caller = $this->checkCallOrigin();
        var_dump(self::$caller);
    }

    private function checkCallOrigin(): bool
    {
        $backtrace = debug_backtrace();
        var_dump($backtrace);
        $strategy = 'GenericDatabase\QueryBuilder';
        if (isset($backtrace[2]) && isset($backtrace[2]['class']) && $backtrace[2]['class'] === $strategy) {
            return true;
        } else {
            return false;
        }
    }

    public static function select(array|string ...$data): IQueryBuilder
    {
        $call = self::$caller ? self::getInstance() : self::getInstance();
        return Internal::select(['type' => Select::DEFAULT , 'data' => $data, 'self' => $call]);
    }

    public static function distinct(array|string ...$data): IQueryBuilder
    {
        $call = self::$caller ? self::getInstance() : self::newInstance();
        return Internal::select(['type' => Select::DISTINCT, 'data' => $data, 'self' => $call]);
    }

    public static function from(array|string ...$data): IQueryBuilder
    {
        return Internal::from(['data' => $data, 'self' => self::getInstance()]);
    }

    public static function join(array|string ...$data): IQueryBuilder
    {
        return Internal::join(
            ['type' => Join::DEFAULT , 'junction' => Junction::NONE, 'data' => $data, 'self' => self::getInstance()]
        );
    }

    /**
     * @param array|string ...$data
     * @return $this
     */
    public static function selfJoin(array|string ...$data): IQueryBuilder
    {
        return Internal::join(
            ['type' => Join::SELF, 'junction' => Junction::NONE, 'data' => $data, 'self' => self::getInstance()]
        );
    }

    /**
     * @param array|string ...$data
     * @return $this
     */
    public static function leftJoin(array|string ...$data): IQueryBuilder
    {
        return Internal::join(
            ['type' => Join::LEFT, 'junction' => Junction::NONE, 'data' => $data, 'self' => self::getInstance()]
        );
    }

    /**
     * @param array|string ...$data
     * @return $this
     */
    public static function rightJoin(array|string ...$data): IQueryBuilder
    {
        return Internal::join(
            ['type' => Join::RIGHT, 'junction' => Junction::NONE, 'data' => $data, 'self' => self::getInstance()]
        );
    }

    /**
     * @param array|string ...$data
     * @return $this
     */
    public static function innerJoin(array|string ...$data): IQueryBuilder
    {
        return Internal::join(
            ['type' => Join::INNER, 'junction' => Junction::NONE, 'data' => $data, 'self' => self::getInstance()]
        );
    }

    /**
     * @param array|string ...$data
     * @return $this
     */
    public static function outerJoin(array|string ...$data): IQueryBuilder
    {
        return Internal::join(
            ['type' => Join::OUTER, 'junction' => Junction::NONE, 'data' => $data, 'self' => self::getInstance()]
        );
    }

    /**
     * @param array|string ...$data
     * @return $this
     */
    public static function crossJoin(array|string ...$data): IQueryBuilder
    {
        return Internal::join(
            ['type' => Join::CROSS, 'junction' => Junction::NONE, 'data' => $data, 'self' => self::getInstance()]
        );
    }

    public static function on(array|string ...$data): IQueryBuilder
    {
        return Internal::on(['junction' => Junction::NONE, 'data' => $data, 'self' => self::getInstance()]);
    }

    /**
     * @param array|string ...$data
     * @return $this
     */
    public static function andOn(array|string ...$data): IQueryBuilder
    {
        return Internal::on(['junction' => Junction::CONJUNCTION, 'data' => $data, 'self' => self::getInstance()]);
    }

    /**
     * @param array|string ...$data
     * @return $this
     */
    public static function orOn(array|string ...$data): IQueryBuilder
    {
        return Internal::on(['junction' => Junction::DISJUNCTION, 'data' => $data, 'self' => self::getInstance()]);
    }

    public static function where(array|string ...$data): IQueryBuilder
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
                    'self' => self::getInstance()
                ];
            }
            return Internal::where($result);
        } else {
            return Internal::where([
                'enum' => Where::class,
                'condition' => Condition::NONE,
                'data' => $data,
                'self' => self::getInstance()
            ]);
        }
    }

    public static function andWhere(array|string ...$data): IQueryBuilder
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
                    'self' => self::getInstance()
                ];
            }
            return Internal::where($result);
        } else {
            return Internal::where([
                'enum' => Where::class,
                'condition' => Condition::CONJUNCTION,
                'data' => $data,
                'self' => self::getInstance()
            ]);
        }
    }

    public static function orWhere(array|string ...$data): IQueryBuilder
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
                    'self' => self::getInstance()
                ];
            }
            return Internal::where($result);
        } else {
            return Internal::where([
                'enum' => Where::class,
                'condition' => Condition::DISJUNCTION,
                'data' => $data,
                'self' => self::getInstance()
            ]);
        }
    }

    public static function having(array|string ...$data): IQueryBuilder
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
                    'self' => self::getInstance()
                ];
            }
            return Internal::having($result);
        } else {
            return Internal::having([
                'enum' => Having::class,
                'condition' => Condition::NONE,
                'data' => $data,
                'self' => self::getInstance()
            ]);
        }
    }

    /**
     * @param array|string ...$data
     * @return $this
     */
    public static function andHaving(array|string ...$data): IQueryBuilder
    {
        if (Arrays::isDepthArray($data) > 2) {
            $result = [];
            foreach (reset($data) as $value) {
                $result[] = [
                    'enum' => Having::class,
                    'condition' => Condition::CONJUNCTION,
                    'data' => [$value],
                    'self' => self::getInstance()
                ];
            }
            return Internal::having($result);
        } else {
            return Internal::having([
                'enum' => Having::class,
                'condition' => Condition::CONJUNCTION,
                'data' => $data,
                'self' => self::getInstance()
            ]);
        }
    }

    /**
     * @param array|string ...$data
     * @return $this
     */
    public static function orHaving(array|string ...$data): IQueryBuilder
    {
        if (Arrays::isDepthArray($data) > 2) {
            $result = [];
            foreach (reset($data) as $value) {
                $result[] = [
                    'enum' => Having::class,
                    'condition' => Condition::DISJUNCTION,
                    'data' => [$value],
                    'self' => self::getInstance()
                ];
            }
            return Internal::having($result);
        } else {
            return Internal::having([
                'enum' => Having::class,
                'condition' => Condition::DISJUNCTION,
                'data' => $data,
                'self' => self::getInstance()
            ]);
        }
    }

    public static function group(array|string ...$data): IQueryBuilder
    {
        return Internal::group(['sorting' => Grouping::DEFAULT , 'data' => $data, 'self' => self::getInstance()]);
    }

    public static function order(array|string ...$data): IQueryBuilder
    {
        return Internal::order(['sorting' => Sorting::NONE, 'data' => $data, 'self' => self::getInstance()]);
    }

    /**
     * @param array|string ...$data
     * @return $this
     */
    public static function orderAsc(array|string ...$data): IQueryBuilder
    {
        return Internal::order(['sorting' => Sorting::ASCENDING, 'data' => $data, 'self' => self::getInstance()]);
    }

    /**
     * @param array|string ...$data
     * @return $this
     */
    public static function orderDesc(array|string ...$data): IQueryBuilder
    {
        return Internal::order(['sorting' => Sorting::DESCENDING, 'data' => $data, 'self' => self::getInstance()]);
    }

    public static function limit(array|string ...$data): IQueryBuilder
    {
        return Internal::limit(['data' => $data, 'self' => self::getInstance()]);
    }

    /**
     * @throws CustomException
     */
    public function build(): string
    {
        return (new Builder($this->query))->build();
    }

    /**
     * @throws CustomException
     */
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
        return FirebirdConnection::getInstance()->getAllMetadata();
    }

    /**
     * @throws ReflectionException
     */
    private static function run($instance): string
    {
        $reflectionMethod = new ReflectionMethod($instance, 'buildRaw');
        $buildRawResult = $reflectionMethod->invoke($instance);
        $builder = new Builder($instance->query);
        return $builder->parse(
            $buildRawResult,
            Translater::SQL_DIALECT_NONE,
            Translater::SQL_DIALECT_SINGLE_QUOTE
        );
    }

    /**
     * @throws ReflectionException
     */
    private static function afterRun($instance): void
    {
        $query = self::run($instance);
        FirebirdConnection::getInstance()->query($query);
    }

    public static function beforeRun(string $query): void
    {
        FirebirdConnection::getInstance()->query($query);
    }

    /**
     * @throws ReflectionException
     */
    public function fetch(int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): mixed
    {
        if ($this->query->build === Build::AFTER) {
            self::afterRun($this);
        }
        return FirebirdConnection::getInstance()->fetch($fetchStyle, $fetchArgument, $optArgs);
    }

    /**
     * @throws ReflectionException
     */
    public function fetchAll(int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): array|bool
    {
        if ($this->query->build === Build::AFTER) {
            self::afterRun($this);
        }
        return FirebirdConnection::getInstance()->fetchAll($fetchStyle, $fetchArgument, $optArgs);
    }
}
