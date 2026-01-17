# Connection

## Connection Strategy

The `GenericDatabase\Connection` class is responsible for establishing and managing database connections. It uses a strategy pattern to support different database engines. The class provides methods for connecting to a database, executing queries, fetching results, and managing the connection state.

## Example Usage

### Load Class and Types Explicitly

```php
// Explicit and simplified module loading with all environment variables
use GenericDatabase\Connection;
use GenericDatabase\Engine\MySQli\MySQL;
use GenericDatabase\Engine\PgSQL\PgSQL;
use GenericDatabase\Engine\SQLSrv\SQLSrv;
use GenericDatabase\Engine\OCI\OCI;
use GenericDatabase\Engine\Firebird\Firebird;
use GenericDatabase\Engine\SQLite\SQLite;
use PDO;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

Dotenv::createImmutable(PATH_ROOT)->load();
```

### Chainable Methods

```php
// Create a new database connection using MySQLi engine in the chainable methods format
$connection = new Connection();
$connection
->setEngine('mysqli')
->setHost($env['MYSQL_HOST'])
->setPort((int)$env['MYSQL_PORT'])
->setDatabase($env['MYSQL_DATABASE'])
->setUser($env['MYSQL_USERNAME'])
->setPassword($env['MYSQL_PASSWORD'])
->setCharset($env['MYSQL_CHARSET'])
->setOptions([
    MySQL::ATTR_PERSISTENT => false,
    MySQL::ATTR_AUTOCOMMIT => true,
    MySQL::ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
    MySQL::ATTR_SET_CHARSET_NAME => "utf8",
    MySQL::ATTR_OPT_INT_AND_FLOAT_NATIVE => true,
    MySQL::ATTR_OPT_CONNECT_TIMEOUT => 28800,
    MySQL::ATTR_OPT_READ_TIMEOUT => 30,
    MySQL::ATTR_READ_DEFAULT_GROUP => "MAX_ALLOWED_PACKET=50M"
])
->setException(true)
->connect();
```

### Fluent Design

```php
// Create a new database connection using MySQLi engine in the fluent design format
$connection = Connection
::setEngine($env['mysqli'])
::setHost($env['MYSQL_HOST'])
::setPort((int)$env['MYSQL_PORT'])
::setDatabase($env['MYSQL_DATABASE'])
::setUser($env['MYSQL_USERNAME'])
::setPassword($env['MYSQL_PASSWORD'])
::setCharset($env['MYSQL_CHARSET'])
::setOptions([
    MySQL::ATTR_PERSISTENT => false,
    MySQL::ATTR_AUTOCOMMIT => true,
    MySQL::ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
    MySQL::ATTR_SET_CHARSET_NAME => "utf8",
    MySQL::ATTR_OPT_INT_AND_FLOAT_NATIVE => true,
    MySQL::ATTR_OPT_CONNECT_TIMEOUT => 28800,
    MySQL::ATTR_OPT_READ_TIMEOUT => 30,
    MySQL::ATTR_READ_DEFAULT_GROUP => "MAX_ALLOWED_PACKET=50M"
])
::setException(true)
->connect();
```

### Static Arguments

```php
// Create a new database connection using MySQLi engine in the static arguments format
$connection = Connection::new(
    engine: 'mysqli',
    host: $_ENV['MYSQL_HOST'],
    port: (int)$_ENV['MYSQL_PORT'],
    database: $_ENV['MYSQL_DATABASE'],
    user: $_ENV['MYSQL_USERNAME'],
    password: $_ENV['MYSQL_PASSWORD'],
    charset: 'utf8',
    options: [
        MySQL::ATTR_PERSISTENT => false,
        MySQL::ATTR_AUTOCOMMIT => true,
        MySQL::ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
        MySQL::ATTR_SET_CHARSET_NAME => "utf8",
        MySQL::ATTR_OPT_INT_AND_FLOAT_NATIVE => true,
        MySQL::ATTR_OPT_CONNECT_TIMEOUT => 28800,
        MySQL::ATTR_OPT_READ_TIMEOUT => 30,
        MySQL::ATTR_READ_DEFAULT_GROUP => "MAX_ALLOWED_PACKET=50M"
    ],
    exception: true
)
->connect();
```

### Static Array

```php
// Create a new database connection using MySQLi engine in the static array format
$connection = Connection::new([
    'engine' => 'mysqli',
    'host' => $_ENV['MYSQL_HOST'],
    'port' => (int)$_ENV['MYSQL_PORT'],
    'database' => $_ENV['MYSQL_DATABASE'],
    'user' => $_ENV['MYSQL_USERNAME'],
    'password' => $_ENV['MYSQL_PASSWORD'],
    'charset' => 'utf8',
    'options' => [
        MySQL::ATTR_PERSISTENT => false,
        MySQL::ATTR_AUTOCOMMIT => true,
        MySQL::ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
        MySQL::ATTR_SET_CHARSET_NAME => "utf8",
        MySQL::ATTR_OPT_INT_AND_FLOAT_NATIVE => true,
        MySQL::ATTR_OPT_CONNECT_TIMEOUT => 28800,
        MySQL::ATTR_OPT_READ_TIMEOUT => 30,
        MySQL::ATTR_READ_DEFAULT_GROUP => "MAX_ALLOWED_PACKET=50M"
    ],
    'exception' => true
])
->connect();
```

## Code Analysis

### Main functionalities

- Establishing and managing database connections
- Supporting different database engines through the strategy pattern
- Executing queries and fetching results
- Managing the connection state (connecting, disconnecting, checking if connected)

### Public Methods

- `setStrategy(IConnection $strategy)`: Sets the strategy instance for the database connection.
- `getStrategy(): IConnection`: Returns the strategy instance for the database connection.
- `connect(): Connection`: Establishes a database connection using the strategy instance.
- `ping(): bool`: Pings the database server to check if the connection is still active.
- `disconnect(): void`: Disconnects from the database.
- `isConnected(): bool`: Checks if the connection is established.
- `quote(mixed ...$params): mixed`: Quotes a string for use in an SQL statement.
- `prepare(mixed ...$params): mixed`: Binds parameters to a prepared query.
- `query(mixed ...$params): mixed`: Executes an SQL statement and returns the result set.
- `exec(mixed ...$params): mixed`: Executes an SQL statement and returns the number of affected rows.
- `fetch(mixed ...$params): mixed`: Fetches the next row from the result set.
- `fetchAll(mixed ...$params): mixed`: Fetches all rows from the result set.
