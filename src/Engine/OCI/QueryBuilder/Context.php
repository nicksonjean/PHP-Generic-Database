<?php

namespace GenericDatabase\Engine\OCI\QueryBuilder;

use GenericDatabase\Connection;
use GenericDatabase\Engine\OCIConnection;

trait Context
{
    protected static $context;

    /**
     * Set default context for all instances
     *
     * @param Connection|OCIConnection $context
     * @return void
     */
    protected static function setContext(Connection|OCIConnection $context): void
    {
        self::$context = $context;
    }

    /**
     * Get context for current instance
     *
     * @return Connection|OCIConnection
     */
    protected function getContext(): Connection|OCIConnection
    {
        return self::$context;
    }
}
