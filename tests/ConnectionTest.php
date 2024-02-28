<?php

namespace GenericDatabase;

use PDO;
use ReflectionException;
use AllowDynamicProperties;
use GenericDatabase\Connection;
use PHPUnit\Framework\TestCase;
use GenericDatabase\Core\Entity;
use GenericDatabase\Helpers\INI;
use GenericDatabase\Helpers\XML;
use GenericDatabase\IConnection;
use GenericDatabase\Helpers\JSON;
use GenericDatabase\Helpers\YAML;
use GenericDatabase\Helpers\Arrays;
use GenericDatabase\Engine\OCIEngine;
use GenericDatabase\Engine\PDOEngine;
use GenericDatabase\Shared\Singleton;
use GenericDatabase\Modules\Chainable;
use GenericDatabase\Engine\PgSQLEngine;
use GenericDatabase\Engine\MySQLiEngine;
use GenericDatabase\Engine\SQLiteEngine;
use GenericDatabase\Engine\SQLSrvEngine;
use GenericDatabase\Helpers\Reflections;
use GenericDatabase\Engine\FirebirdEngine;

class ConnectionTest extends TestCase
{
    public function testConnectionSingleton()
    {
        $connection1 = Connection::getInstance();
        $connection2 = Connection::getInstance();

        $this->assertInstanceOf(Connection::class, $connection1);
        $this->assertInstanceOf(Connection::class, $connection2);
        $this->assertSame($connection1, $connection2);
    }
}
