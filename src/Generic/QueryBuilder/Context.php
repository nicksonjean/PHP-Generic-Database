<?php

namespace GenericDatabase\Generic\QueryBuilder;

use GenericDatabase\Interfaces\IConnection;

trait Context
{
    /**
     * Property to store settings
     * @var IConnection $context
     */
    protected static IConnection $context;

    /**
     * Set default context for all instances
     *
     * @param IConnection $context
     * @return void
     */
    protected static function setContext(IConnection $context): void
    {
        self::$context = $context;
    }

    /**
     * Get context for current instance
     *
     * @return IConnection
     */
    protected function getContext(): IConnection
    {
        return self::$context;
    }
}

