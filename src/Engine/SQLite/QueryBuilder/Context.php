<?php

namespace GenericDatabase\Engine\SQLite\QueryBuilder;

use GenericDatabase\Connection;
use GenericDatabase\Engine\SQLiteConnection;

trait Context
{
    protected static $context;

    /**
     * Set default context for all instances
     *
     * @param Connection|SQLiteConnection $context
     * @return void
     */
    protected static function setContext(Connection|SQLiteConnection $context): void
    {
        self::$context = $context;
    }

    /**
     * Get context for current instance
     *
     * @return Connection|SQLiteConnection
     */
    protected function getContext(): Connection|SQLiteConnection
    {
        return self::$context;
    }
}
