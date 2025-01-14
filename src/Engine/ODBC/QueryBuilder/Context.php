<?php

namespace GenericDatabase\Engine\ODBC\QueryBuilder;

use GenericDatabase\Connection;
use GenericDatabase\Engine\ODBCConnection;

trait Context
{
    protected static $context;

    /**
     * Set default context for all instances
     *
     * @param Connection|ODBCConnection $context
     * @return void
     */
    protected static function setContext(Connection|ODBCConnection $context): void
    {
        self::$context = $context;
    }

    /**
     * Get context for current instance
     *
     * @return Connection|ODBCConnection
     */
    protected function getContext(): Connection|ODBCConnection
    {
        return self::$context;
    }
}
