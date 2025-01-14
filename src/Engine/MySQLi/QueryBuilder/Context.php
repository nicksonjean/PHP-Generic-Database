<?php

namespace GenericDatabase\Engine\MySQLi\QueryBuilder;

use GenericDatabase\Connection;
use GenericDatabase\Engine\MySQLiConnection;

trait Context
{
    protected static $context;

    /**
     * Set default context for all instances
     *
     * @param Connection|MySQLiConnection $context
     * @return void
     */
    protected static function setContext(Connection|MySQLiConnection $context): void
    {
        self::$context = $context;
    }

    /**
     * Get context for current instance
     *
     * @return Connection|MySQLiConnection
     */
    protected function getContext(): Connection|MySQLiConnection
    {
        return self::$context;
    }
}
