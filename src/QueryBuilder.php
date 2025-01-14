<?php

declare(strict_types=1);

namespace GenericDatabase;

use GenericDatabase\IQueryBuilder;
use GenericDatabase\Shared\Singleton;
use GenericDatabase\Connection;
use GenericDatabase\Engine\FirebirdQueryBuilder;
use GenericDatabase\Engine\OCIQueryBuilder;
use GenericDatabase\Engine\PgSQLQueryBuilder;
use GenericDatabase\Engine\MySQLiQueryBuilder;
use GenericDatabase\Engine\SQLSrvQueryBuilder;
use GenericDatabase\Engine\SQLiteQueryBuilder;
use GenericDatabase\Engine\PDOQueryBuilder;
use GenericDatabase\Engine\ODBCQueryBuilder;
use Exception;

class QueryBuilder implements IQueryBuilder, IQueryBuilderStrategy
{
    use Singleton;

    /**
     * Property of the type object who define the connection
     */
    private static Connection $context;

    private static $self;

    /**
     * Property of the type object who define the strategy
     */
    private IQueryBuilder $strategy;

    /**
     * @throws Exception
     */
    public function __construct(Connection $context = null)
    {
        self::$context = $context;
        self::$self = $this;
        $this->initStrategy();
    }

    /**
     * Static initializer with context
     *
     * @param Connection $context
     * @return class-string<static>
     */
    public static function with(Connection $context): string
    {
        self::$context = $context;
        self::$self = new static($context);
        return static::class;
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
    private function initStrategy(): void
    {
        $engine = self::$context->getEngine();

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
            default => null,
        };

        if ($strategy === null) {
            throw new Exception('No valid strategy found');
        }

        $this->setStrategy($strategy);
    }

    public static function select(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->select(...$data);
        return self::$self;
    }

    public static function distinct(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->distinct(...$data);
        return self::$self;
    }

    public static function from(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->from(...$data);
        return self::$self;
    }

    public static function join(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->join(...$data);
        return self::$self;
    }

    public static function selfJoin(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->selfJoin(...$data);
        return self::$self;
    }

    public static function leftJoin(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->leftJoin(...$data);
        return self::$self;
    }

    public static function rightJoin(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->rightJoin(...$data);
        return self::$self;
    }

    public static function innerJoin(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->innerJoin(...$data);
        return self::$self;
    }

    public static function outerJoin(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->outerJoin(...$data);
        return self::$self;
    }

    public static function crossJoin(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->crossJoin(...$data);
        return self::$self;
    }

    public static function on(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->on(...$data);
        return self::$self;
    }

    public static function andOn(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->andOn(...$data);
        return self::$self;
    }

    public static function orOn(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->orOn(...$data);
        return self::$self;
    }

    public static function where(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->where(...$data);
        return self::$self;
    }

    public static function andWhere(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->andWhere(...$data);
        return self::$self;
    }

    public static function orWhere(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->andWhere(...$data);
        return self::$self;
    }

    public static function having(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->having(...$data);
        return self::$self;
    }

    public static function andHaving(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->andHaving(...$data);
        return self::$self;
    }

    public static function orHaving(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->orHaving(...$data);
        return self::$self;
    }

    public static function group(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->group(...$data);
        return self::$self;
    }

    public static function order(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->order(...$data);
        return self::$self;
    }

    public static function orderAsc(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->orderAsc(...$data);
        return self::$self;
    }

    public static function orderDesc(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->orderDesc(...$data);
        return self::$self;
    }

    public static function limit(array|string ...$data): IQueryBuilder
    {
        self::$self->getStrategy()->limit(...$data);
        return self::$self;
    }

    public function build(): string
    {
        return self::$self->getStrategy()->build();
    }

    public function buildRaw(): string
    {
        return self::$self->getStrategy()->buildRaw();
    }

    public function getValues(): array
    {
        return self::$self->getStrategy()->getValues();
    }

    public function getAllMetadata(): object
    {
        return self::$self->getStrategy()->getAllMetadata();
    }

    public function fetch(int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): mixed
    {
        return self::$self->getStrategy()->fetch($fetchStyle, $fetchArgument, $optArgs);
    }

    public function fetchAll(int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): array|bool
    {
        return self::$self->getStrategy()->fetchAll($fetchStyle, $fetchArgument, $optArgs);
    }
}
