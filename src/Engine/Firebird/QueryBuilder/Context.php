<?php

namespace GenericDatabase\Engine\Firebird\QueryBuilder;

use GenericDatabase\Connection;
use GenericDatabase\Engine\FirebirdConnection;

trait Context
{
    protected static $context;

    /**
     * Set default context for all instances
     *
     * @param Connection|FirebirdConnection $context
     * @return void
     */
    protected static function setContext(Connection|FirebirdConnection $context): void
    {
        self::$context = $context;
    }

    /**
     * Get context for current instance
     *
     * @return Connection|FirebirdConnection
     */
    protected function getContext(): Connection|FirebirdConnection
    {
        return self::$context;
    }
}
