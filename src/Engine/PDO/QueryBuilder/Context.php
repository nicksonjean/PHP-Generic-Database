<?php

namespace GenericDatabase\Engine\PDO\QueryBuilder;

use GenericDatabase\Connection;
use GenericDatabase\Engine\PDOConnection;

trait Context
{
    protected static $context;

    /**
     * Set default context for all instances
     *
     * @param Connection|PDOConnection $context
     * @return void
     */
    protected static function setContext(Connection|PDOConnection $context): void
    {
        self::$context = $context;
    }

    /**
     * Get context for current instance
     *
     * @return Connection|PDOConnection
     */
    protected function getContext(): Connection|PDOConnection
    {
        return self::$context;
    }
}
