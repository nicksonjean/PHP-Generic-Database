<?php

namespace GenericDatabase\Engine\SQLSrv\QueryBuilder;

use GenericDatabase\Connection;
use GenericDatabase\Engine\SQLSrvConnection;

trait Context
{
    protected static $context;

    /**
     * Set default context for all instances
     *
     * @param Connection|SQLSrvConnection $context
     * @return void
     */
    protected static function setContext(Connection|SQLSrvConnection $context): void
    {
        self::$context = $context;
    }

    /**
     * Get context for current instance
     *
     * @return Connection|SQLSrvConnection
     */
    protected function getContext(): Connection|SQLSrvConnection
    {
        return self::$context;
    }
}
