<?php

declare(strict_types=1);

namespace GenericDatabase\Interfaces;

/**
 * Defines an interface for a query builder that can be used to construct SQL queries.
 * The query builder provides a fluent API for building complex queries with features like
 * select, join, where, order, limit, and more.
 *
 * @package PHP-Generic-Database\Interfaces
 * @subpackage IQueryBuilder
 */
interface IQueryBuilder
{
    /**
     * Specifies the columns to select in the query.
     *
     * @param array|string ...$data The columns to select.
     * @return IQueryBuilder The query builder instance.
     */
    public static function select(array|string ...$data): IQueryBuilder;

    /**
     * Specifies that the query should return distinct results.
     *
     * @param array|string ...$data The columns to select distinctly.
     * @return IQueryBuilder The query builder instance.
     */
    public static function distinct(array|string ...$data): IQueryBuilder;

    /**
     * Specifies the table(s) to select from.
     *
     * @param array|string ...$data The table(s) to select from.
     * @return IQueryBuilder The query builder instance.
     */
    public static function from(array|string ...$data): IQueryBuilder;

    /**
     * Adds a join clause to the query.
     *
     * @param array|string ...$data The join clause parameters.
     * @return IQueryBuilder The query builder instance.
     */
    public static function join(array|string ...$data): IQueryBuilder;

    /**
     * Adds a self join clause to the query.
     *
     * @param array|string ...$data The self join clause parameters.
     * @return IQueryBuilder The query builder instance.
     */
    public static function selfJoin(array|string ...$data): IQueryBuilder;

    /**
     * Adds a left join clause to the query.
     *
     * @param array|string ...$data The left join clause parameters.
     * @return IQueryBuilder The query builder instance.
     */
    public static function leftJoin(array|string ...$data): IQueryBuilder;

    /**
     * Adds a right join clause to the query.
     *
     * @param array|string ...$data The right join clause parameters.
     * @return IQueryBuilder The query builder instance.
     */
    public static function rightJoin(array|string ...$data): IQueryBuilder;

    /**
     * Adds an inner join clause to the query.
     *
     * @param array|string ...$data The inner join clause parameters.
     * @return IQueryBuilder The query builder instance.
     */
    public static function innerJoin(array|string ...$data): IQueryBuilder;

    /**
     * Adds an outer join clause to the query.
     *
     * @param array|string ...$data The outer join clause parameters.
     * @return IQueryBuilder The query builder instance.
     */
    public static function outerJoin(array|string ...$data): IQueryBuilder;

    /**
     * Adds a cross join clause to the query.
     *
     * @param array|string ...$data The cross join clause parameters.
     * @return IQueryBuilder The query builder instance.
     */
    public static function crossJoin(array|string ...$data): IQueryBuilder;

    /**
     * Adds an ON clause to the current join.
     *
     * @param array|string ...$data The ON clause parameters.
     * @return IQueryBuilder The query builder instance.
     */
    public static function on(array|string ...$data): IQueryBuilder;

    /**
     * Adds an AND ON clause to the current join.
     *
     * @param array|string ...$data The AND ON clause parameters.
     * @return IQueryBuilder The query builder instance.
     */
    public static function andOn(array|string ...$data): IQueryBuilder;

    /**
     * Adds an OR ON clause to the current join.
     *
     * @param array|string ...$data The OR ON clause parameters.
     * @return IQueryBuilder The query builder instance.
     */
    public static function orOn(array|string ...$data): IQueryBuilder;

    /**
     * Adds a WHERE clause to the query.
     *
     * @param array|string ...$data The WHERE clause parameters.
     * @return IQueryBuilder The query builder instance.
     */
    public static function where(array|string ...$data): IQueryBuilder;

    /**
     * Adds an AND WHERE clause to the query.
     *
     * @param array|string ...$data The AND WHERE clause parameters.
     * @return IQueryBuilder The query builder instance.
     */
    public static function andWhere(array|string ...$data): IQueryBuilder;

    /**
     * Adds an OR WHERE clause to the query.
     *
     * @param array|string ...$data The OR WHERE clause parameters.
     * @return IQueryBuilder The query builder instance.
     */
    public static function orWhere(array|string ...$data): IQueryBuilder;

    /**
     * Adds a HAVING clause to the query.
     *
     * @param array|string ...$data The HAVING clause parameters.
     * @return IQueryBuilder The query builder instance.
     */
    public static function having(array|string ...$data): IQueryBuilder;

    /**
     * Adds an AND HAVING clause to the query.
     *
     * @param array|string ...$data The AND HAVING clause parameters.
     * @return IQueryBuilder The query builder instance.
     */
    public static function andHaving(array|string ...$data): IQueryBuilder;

    /**
     * Adds an OR HAVING clause to the query.
     *
     * @param array|string ...$data The OR HAVING clause parameters.
     * @return IQueryBuilder The query builder instance.
     */
    public static function orHaving(array|string ...$data): IQueryBuilder;

    /**
     * Adds a GROUP BY clause to the query.
     *
     * @param array|string ...$data The GROUP BY clause parameters.
     * @return IQueryBuilder The query builder instance.
     */
    public static function group(array|string ...$data): IQueryBuilder;

    /**
     * Adds an ORDER BY clause to the query.
     *
     * @param array|string ...$data The ORDER BY clause parameters.
     * @return IQueryBuilder The query builder instance.
     */
    public static function order(array|string ...$data): IQueryBuilder;

    /**
     * Adds an ORDER BY clause with ASC sorting to the query.
     *
     * @param array|string ...$data The ORDER BY clause parameters.
     * @return IQueryBuilder The query builder instance.
     */
    public static function orderAsc(array|string ...$data): IQueryBuilder;

    /**
     * Adds an ORDER BY clause with DESC sorting to the query.
     *
     * @param array|string ...$data The ORDER BY clause parameters.
     * @return IQueryBuilder The query builder instance.
     */
    public static function orderDesc(array|string ...$data): IQueryBuilder;

    /**
     * Adds a LIMIT clause to the query.
     *
     * @param array|string ...$data The LIMIT clause parameters.
     * @return IQueryBuilder The query builder instance.
     */
    public static function limit(array|string ...$data): IQueryBuilder;

    /**
     * Builds the final SQL query string.
     *
     * @return string The SQL query string.
     */
    public function build(): string;

    /**
     * Builds the final SQL query string without any modifications.
     *
     * @return string The raw SQL query string.
     */
    public function buildRaw(): string;

    /**
     * Gets the values that will be bound to the query.
     *
     * @return array The bound values.
     */
    public function getValues(): array;

    /**
     * Gets all the metadata associated with the query.
     *
     * @return object The query metadata.
     */
    public function getAllMetadata(): object;

    /**
     * Executes the query and returns the result.
     *
     * @param int|null $fetchStyle The fetch style to use.
     * @param mixed|null $fetchArgument The fetch argument to use.
     * @param mixed|null $optArgs Additional options for the fetch operation.
     * @return mixed The query result.
     */
    public function fetch(?int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): mixed;

    /**
     * Executes the query and returns all the results.
     *
     * @param int|null $fetchStyle The fetch style to use.
     * @param mixed|null $fetchArgument The fetch argument to use.
     * @param mixed|null $optArgs Additional options for the fetch operation.
     * @return array|bool The query results or false on failure.
     */
    public function fetchAll(?int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): array|bool;
}
