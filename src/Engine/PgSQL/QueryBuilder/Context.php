<?php

namespace GenericDatabase\Engine\PgSQL\QueryBuilder;

use GenericDatabase\Connection;
use GenericDatabase\Engine\PgSQLConnection;

trait Context
{
    protected static $context;

    /**
     * Set default context for all instances
     *
     * @param Connection|PgSQLConnection $context
     * @return void
     */
    protected static function setContext(Connection|PgSQLConnection $context): void
    {
        self::$context = $context;
    }

    /**
     * Get context for current instance
     *
     * @return Connection|PgSQLConnection
     */
    protected function getContext(): Connection|PgSQLConnection
    {
        return self::$context;
    }
}
