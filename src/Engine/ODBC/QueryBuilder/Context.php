<?php

namespace GenericDatabase\Engine\ODBC\QueryBuilder;

use GenericDatabase\Interfaces\IConnection;

trait Context
{
    protected static $context;

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
