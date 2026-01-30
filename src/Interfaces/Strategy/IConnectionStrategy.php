<?php

declare(strict_types=1);

namespace GenericDatabase\Interfaces\Strategy;

use GenericDatabase\Interfaces\IConnection;

/**
 *
 * This is an interface definition for `IConnectionStrategy`. Here's what each method does:
 *
 * Methods:
 * - `setStrategy(IConnection $strategy)`: Sets the strategy instance for the connection. It takes an `IConnection` object as a parameter and returns nothing (`void`).
 * - `getStrategy()`: Returns the currently set strategy instance as an `IConnection` object.
 *
 * @package PHP-Generic-Database\Interfaces\Strategy
 * @subpackage IConnectionStrategy
 */
interface IConnectionStrategy
{
    /**
     * Defines the strategy instance
     *
     * @param IConnection $strategy
     * @return void
     */
    public function setStrategy(IConnection $strategy): void;

    /**
     * Get the strategy instance
     *
     * @return IConnection
     */
    public function getStrategy(): IConnection;
}
