<?php

declare(strict_types=1);

namespace GenericDatabase\Interfaces\Strategy;

use GenericDatabase\Interfaces\IQueryBuilder;

/**
 * This is an interface definition for `IQueryBuilderStrategy`. Here's what each method does:
 *
 * Methods:
 * - `setStrategy(IQueryBuilder $strategy)`: Sets the query builder strategy instance. It takes an `IQueryBuilder` object as a parameter and returns nothing (`void`).
 * - `getStrategy()`: Returns the currently set query builder strategy instance as an `IQueryBuilder` object.
 *
 * @package PHP-Generic-Database\Interfaces\Strategy
 * @subpackage IQueryBuilderStrategy
 */
interface IQueryBuilderStrategy
{
    /**
     * Defines the strategy instance
     *
     * @param IQueryBuilder $strategy
     * @return void
     */
    public function setStrategy(IQueryBuilder $strategy): void;

    /**
     * Get the strategy instance
     *
     * @return IQueryBuilder
     */
    public function getStrategy(): IQueryBuilder;
}
